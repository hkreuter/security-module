<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\OTP\Notifier\Email;

use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\UserInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Email\OtpMailContent;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Email\OtpMailRendererInterface;
use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\EmailFactoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OtpEmailContentRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\UserRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\Email\OtpEmailNotifier;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OtpEmailNotifierTest extends TestCase
{
    #[Test]
    public function notifySendsRenderedHtmlMailWhenCmsContentExists(): void
    {
        $email = uniqid() . '@example.com';
        $code = (string) random_int(100000, 999999);
        $subject = uniqid();
        $html = uniqid() . " $code " . uniqid();
        $plain = uniqid() . " $code";

        $contentRepositoryMock = $this->createMock(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryMock->expects($this->once())
            ->method('getEmailSubject')
            ->with(OtpMailContent::IDENT)
            ->willReturn($subject);

        $rendererMock = $this->createMock(OtpMailRendererInterface::class);
        $rendererMock->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) use ($code, $html, $plain): string {
                $this->assertSame(OtpMailContent::IDENT, $data['contentIdent']);
                $this->assertSame($code, $data['otp']);

                return str_contains($template, '/html/') ? $html : $plain;
            });

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->method('getShop')->willReturn($this->createStub(Shop::class));
        $emailModelMock->expects($this->once())->method('setFrom');
        $emailModelMock->expects($this->once())->method('setSubject')->with($subject);
        $emailModelMock->expects($this->once())->method('setBody')->with($html);
        $emailModelMock->expects($this->once())->method('setAltBody')->with($plain);
        $emailModelMock->expects($this->once())->method('setRecipient')->with($email, '');
        $emailModelMock->expects($this->once())->method('send')->willReturn(true);
        $emailModelMock->expects($this->never())->method('sendEmail');

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            userRepository: $this->userRepositoryReturning($email),
            contentRepository: $contentRepositoryMock,
            renderer: $rendererMock,
        );

        $sut->notify(userId: uniqid(), code: $code);
    }

    #[Test]
    public function notifyPassesConfiguredLifetimeAsMinutesToTemplate(): void
    {
        $code = (string) random_int(100000, 999999);
        $html = uniqid() . " $code " . uniqid();
        $plain = uniqid() . " $code";

        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererMock = $this->createMock(OtpMailRendererInterface::class);
        $rendererMock->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) use ($html, $plain): string {
                // 300 s configured lifetime -> "5" minutes in the mail copy
                $this->assertSame(5, $data['minutes']);

                return str_contains($template, '/html/') ? $html : $plain;
            });

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->method('getShop')->willReturn($this->createStub(Shop::class));
        $emailModelMock->expects($this->once())->method('send')->willReturn(true);

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            userRepository: $this->userRepositoryReturning(uniqid() . '@example.com'),
            contentRepository: $contentRepositoryStub,
            renderer: $rendererMock,
            settings: $this->settingsWithLifetime(300),
        );

        $sut->notify(userId: uniqid(), code: $code);
    }

    #[Test]
    public function notifyFallbackShowsConfiguredLifetimeMinutes(): void
    {
        $email = uniqid() . '@example.com';
        $code = (string) random_int(100000, 999999);
        $subject = uniqid();
        $bodyTemplate = uniqid() . ' code %s valid %d min';

        $translations = ['OTP_EMAIL_SUBJECT' => $subject, 'OTP_EMAIL_BODY' => $bodyTemplate];
        $shopAdapterStub = $this->createStub(ShopAdapterInterface::class);
        $shopAdapterStub->method('translateString')->willReturnCallback(
            fn(string $key): string => $translations[$key] ?? $key
        );

        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(null); // force fallback

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->expects($this->once())
            ->method('sendEmail')
            // 120 s -> 2 minutes
            ->with($email, $subject, sprintf($bodyTemplate, $code, 2));

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            userRepository: $this->userRepositoryReturning($email),
            shopAdapter: $shopAdapterStub,
            contentRepository: $contentRepositoryStub,
            settings: $this->settingsWithLifetime(120),
        );

        $sut->notify(userId: uniqid(), code: $code);
    }

    #[Test]
    public function notifyLogsWarningAndFallsBackWhenRendererThrows(): void
    {
        $code = (string) random_int(100000, 999999);

        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererStub = $this->createStub(OtpMailRendererInterface::class);
        $rendererStub->method('render')->willThrowException(new \RuntimeException(uniqid()));

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning')
            ->willReturnCallback(
                fn(string $message, array $context = []) => $this->assertCodeNotLogged($code, $message, $context)
            );

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->expects($this->once())->method('sendEmail'); // fallback still delivers

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            userRepository: $this->userRepositoryReturning(uniqid() . '@example.com'),
            contentRepository: $contentRepositoryStub,
            renderer: $rendererStub,
            logger: $loggerMock,
        );

        $sut->notify(userId: uniqid(), code: $code);
    }

    #[Test]
    public function notifyLogsWarningAndFallsBackWhenRenderedBodyMissesCode(): void
    {
        $code = (string) random_int(100000, 999999);

        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererStub = $this->createStub(OtpMailRendererInterface::class);
        $rendererStub->method('render')->willReturn(uniqid()); // rendered body without the code

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning')
            ->willReturnCallback(
                fn(string $message, array $context = []) => $this->assertCodeNotLogged($code, $message, $context)
            );

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->expects($this->once())->method('sendEmail');

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            userRepository: $this->userRepositoryReturning(uniqid() . '@example.com'),
            contentRepository: $contentRepositoryStub,
            renderer: $rendererStub,
            logger: $loggerMock,
        );

        $sut->notify(userId: uniqid(), code: $code);
    }

    #[Test]
    public function notifyLogsWarningAndFallsBackWhenCmsMailSendFails(): void
    {
        $code = (string) random_int(100000, 999999);
        $html = uniqid() . " $code " . uniqid();
        $plain = uniqid() . " $code";

        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererStub = $this->createStub(OtpMailRendererInterface::class);
        $rendererStub->method('render')->willReturnCallback(
            fn(string $template): string => str_contains($template, '/html/') ? $html : $plain
        );

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning')
            ->willReturnCallback(
                fn(string $message, array $context = []) => $this->assertCodeNotLogged($code, $message, $context)
            );

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->method('getShop')->willReturn($this->createStub(Shop::class));
        $emailModelMock->expects($this->once())->method('send')->willReturn(false);
        $emailModelMock->expects($this->once())->method('sendEmail'); // fallback delivers after the CMS mail send fails

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            userRepository: $this->userRepositoryReturning(uniqid() . '@example.com'),
            contentRepository: $contentRepositoryStub,
            renderer: $rendererStub,
            logger: $loggerMock,
        );

        $sut->notify(userId: uniqid(), code: $code);
    }

    #[Test]
    public function notifyFallsBackToPlainMailWhenNoCmsContent(): void
    {
        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(null);

        $this->assertPlainFallbackIsSent($contentRepositoryStub, $this->createStub(OtpMailRendererInterface::class));
    }

    #[Test]
    public function notifyFallsBackToPlainMailWhenRendererThrows(): void
    {
        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererStub = $this->createStub(OtpMailRendererInterface::class);
        $rendererStub->method('render')->willThrowException(new \RuntimeException(uniqid()));

        $this->assertPlainFallbackIsSent($contentRepositoryStub, $rendererStub);
    }

    #[Test]
    public function notifyFallsBackToPlainMailWhenRenderedHtmlMissesTheCode(): void
    {
        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererStub = $this->createStub(OtpMailRendererInterface::class);
        $rendererStub->method('render')->willReturn(uniqid());

        $this->assertPlainFallbackIsSent($contentRepositoryStub, $rendererStub);
    }

    #[Test]
    public function notifyFallsBackToPlainMailWhenRenderedPlainMissesTheCode(): void
    {
        $code = (string) random_int(100000, 999999);

        $contentRepositoryStub = $this->createStub(OtpEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererStub = $this->createStub(OtpMailRendererInterface::class);
        $rendererStub->method('render')->willReturnCallback(
            fn(string $template): string => str_contains($template, '/html/')
                ? uniqid() . " $code " . uniqid()
                : uniqid()
        );

        $this->assertPlainFallbackIsSent($contentRepositoryStub, $rendererStub, $code);
    }

    private function assertPlainFallbackIsSent(
        OtpEmailContentRepositoryInterface $contentRepository,
        OtpMailRendererInterface $renderer,
        ?string $code = null,
    ): void {
        $email = uniqid() . '@example.com';
        $code ??= (string) random_int(100000, 999999);
        $subject = uniqid();
        $bodyTemplate = uniqid() . ' %s';

        $translations = [
            'OTP_EMAIL_SUBJECT' => $subject,
            'OTP_EMAIL_BODY'    => $bodyTemplate,
        ];
        $shopAdapterStub = $this->createStub(ShopAdapterInterface::class);
        $shopAdapterStub->method('translateString')->willReturnCallback(
            fn(string $key): string => $translations[$key] ?? $key
        );

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->expects($this->once())
            ->method('sendEmail')
            ->with($email, $subject, sprintf($bodyTemplate, $code));
        $emailModelMock->expects($this->never())->method('send');

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            userRepository: $this->userRepositoryReturning($email),
            shopAdapter: $shopAdapterStub,
            contentRepository: $contentRepository,
            renderer: $renderer,
        );

        $sut->notify(userId: uniqid(), code: $code);
    }

    private function emailFactoryReturning(Email $email): EmailFactoryInterface
    {
        $emailFactoryStub = $this->createStub(EmailFactoryInterface::class);
        $emailFactoryStub->method('create')->willReturn($email);

        return $emailFactoryStub;
    }

    private function userRepositoryReturning(string $email): UserRepositoryInterface
    {
        $userStub = $this->createStub(UserInterface::class);
        $userStub->method('getEmail')->willReturn($email);

        $userRepositoryStub = $this->createStub(UserRepositoryInterface::class);
        $userRepositoryStub->method('getUserById')->willReturn($userStub);

        return $userRepositoryStub;
    }

    private function assertCodeNotLogged(string $code, string $message, array $context): void
    {
        $this->assertStringNotContainsString($code, $message);
        $this->assertStringNotContainsString($code, (string) json_encode($context));
    }

    private function settingsWithLifetime(int $seconds): TwoFAShopSettingsInterface
    {
        $stub = $this->createStub(TwoFAShopSettingsInterface::class);
        $stub->method('getOtpCodeLifetime')->willReturn($seconds);

        return $stub;
    }

    private function getSut(
        ?EmailFactoryInterface $emailFactory = null,
        ?UserRepositoryInterface $userRepository = null,
        ?ShopAdapterInterface $shopAdapter = null,
        ?OtpEmailContentRepositoryInterface $contentRepository = null,
        ?OtpMailRendererInterface $renderer = null,
        ?TwoFAShopSettingsInterface $settings = null,
        ?LoggerInterface $logger = null,
    ): OtpEmailNotifier {
        return new OtpEmailNotifier(
            emailFactory: $emailFactory ?? $this->createStub(EmailFactoryInterface::class),
            userRepository: $userRepository ?? $this->createStub(UserRepositoryInterface::class),
            shopAdapter: $shopAdapter ?? $this->createStub(ShopAdapterInterface::class),
            contentRepository: $contentRepository ?? $this->createStub(OtpEmailContentRepositoryInterface::class),
            renderer: $renderer ?? $this->createStub(OtpMailRendererInterface::class),
            settings: $settings ?? $this->createStub(TwoFAShopSettingsInterface::class),
            logger: $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
