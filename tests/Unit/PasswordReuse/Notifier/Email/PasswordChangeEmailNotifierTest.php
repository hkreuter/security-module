<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Notifier\Email;

use DateTimeImmutable;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountData;
use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\AccountNotFoundException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailContent;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailRendererInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordChangeEmailContentRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Notifier\Email\PasswordChangeEmailNotifier;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\EmailFactoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PasswordChangeEmailNotifierTest extends TestCase
{
    private const SUBJECT_KEY = 'OESM_PASSWORDCHANGE_EMAIL_SUBJECT';
    private const BODY_KEY = 'OESM_PASSWORDCHANGE_EMAIL_BODY';

    #[Test]
    public function notifySendsRenderedCmsMailWhenContentActive(): void
    {
        $email = uniqid() . '@example.com';
        $subject = uniqid();
        $changedAt = new DateTimeImmutable('2026-08-21 14:30:00');
        $formatted = '2026-08-21 14:30';
        $html = uniqid() . " $formatted " . uniqid();
        $plain = uniqid() . " $formatted";

        $contentRepositoryMock = $this->createMock(PasswordChangeEmailContentRepositoryInterface::class);
        $contentRepositoryMock->expects($this->once())
            ->method('getEmailSubject')
            ->with(PasswordChangeMailContent::IDENT)
            ->willReturn($subject);

        $rendererMock = $this->createMock(PasswordChangeMailRendererInterface::class);
        $rendererMock->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) use ($formatted, $html, $plain): string {
                $this->assertSame(PasswordChangeMailContent::IDENT, $data['contentIdent']);
                $this->assertSame($formatted, $data['changedAt']);

                return str_contains($template, '/html/') ? $html : $plain;
            });

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->method('getShop')->willReturn($this->createStub(Shop::class));
        $emailModelMock->expects($this->once())->method('setSubject')->with($subject);
        $emailModelMock->expects($this->once())->method('setBody')->with($html);
        $emailModelMock->expects($this->once())->method('setAltBody')->with($plain);
        $emailModelMock->expects($this->once())->method('setRecipient')->with($email, '');
        $emailModelMock->expects($this->once())->method('send')->willReturn(true);
        $emailModelMock->expects($this->never())->method('sendEmail');

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            accountTypeResolver: $this->accountResolverReturning($this->account($email, 1)),
            contentRepository: $contentRepositoryMock,
            renderer: $rendererMock,
            language: $this->languageWithAbbr(1, 'en'),
        );

        $sut->notify(affectedUserId: uniqid(), changedAt: $changedAt);
    }

    #[Test]
    public function notifyFallsBackToPlainMailWhenCmsContentAbsent(): void
    {
        $email = uniqid() . '@example.com';
        $subject = uniqid();
        $bodyTemplate = uniqid() . ' changed on %s';
        $changedAt = new DateTimeImmutable('2026-08-21 14:30:00');
        $formatted = '2026-08-21 14:30';

        $contentRepositoryStub = $this->createStub(PasswordChangeEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(null);

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->expects($this->once())
            ->method('sendEmail')
            ->with($email, $subject, sprintf($bodyTemplate, $formatted));
        $emailModelMock->expects($this->never())->method('send');

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            accountTypeResolver: $this->accountResolverReturning($this->account($email, 1)),
            contentRepository: $contentRepositoryStub,
            shopAdapter: $this->shopAdapterWith([self::SUBJECT_KEY => $subject, self::BODY_KEY => $bodyTemplate]),
            language: $this->languageWithAbbr(1, 'en'),
        );

        $sut->notify(affectedUserId: uniqid(), changedAt: $changedAt);
    }

    #[Test]
    public function notifyFallsBackWhenRenderedBodyMissesTheTimestampPlaceholder(): void
    {
        $contentRepositoryStub = $this->createStub(PasswordChangeEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(uniqid());

        $rendererStub = $this->createStub(PasswordChangeMailRendererInterface::class);
        $rendererStub->method('render')->willReturn(uniqid()); // rendered body without the timestamp

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning');

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->expects($this->once())->method('sendEmail');
        $emailModelMock->expects($this->never())->method('send');

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            accountTypeResolver: $this->accountResolverReturning($this->account(uniqid() . '@example.com', 1)),
            contentRepository: $contentRepositoryStub,
            renderer: $rendererStub,
            language: $this->languageWithAbbr(1, 'en'),
            logger: $loggerMock,
        );

        $sut->notify(affectedUserId: uniqid(), changedAt: new DateTimeImmutable());
    }

    #[Test]
    public function notifyResolvesRecipientAndSwitchesToAffectedAccountLanguage(): void
    {
        $email = uniqid() . '@example.com';
        $languageId = 5;
        $subject = uniqid();
        $bodyTemplate = uniqid() . ' %s';
        $changedAt = new DateTimeImmutable('2026-08-21 14:30:00');
        $formattedGerman = '21.08.2026 14:30';

        $contentRepositoryStub = $this->createStub(PasswordChangeEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(null);

        $baseLanguageCalls = [];
        $languageMock = $this->createMock(Language::class);
        $languageMock->method('getLanguageAbbr')->with($languageId)->willReturn('de');
        $languageMock->method('setBaseLanguage')->willReturnCallback(
            function (?int $lang = null) use (&$baseLanguageCalls): void {
                $baseLanguageCalls[] = $lang;
            }
        );

        $emailModelMock = $this->createMock(Email::class);
        $emailModelMock->expects($this->once())
            ->method('sendEmail')
            ->with($email, $subject, sprintf($bodyTemplate, $formattedGerman));

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelMock),
            accountTypeResolver: $this->accountResolverReturning($this->account($email, $languageId)),
            contentRepository: $contentRepositoryStub,
            shopAdapter: $this->shopAdapterWith([self::SUBJECT_KEY => $subject, self::BODY_KEY => $bodyTemplate]),
            language: $languageMock,
        );

        $sut->notify(affectedUserId: uniqid(), changedAt: $changedAt);

        $this->assertContains(
            $languageId,
            $baseLanguageCalls,
            'The mail is composed in the affected account language.',
        );
    }

    #[Test]
    public function notifyLogsWarningAndSwallowsWhenSendingFails(): void
    {
        $contentRepositoryStub = $this->createStub(PasswordChangeEmailContentRepositoryInterface::class);
        $contentRepositoryStub->method('getEmailSubject')->willReturn(null);

        $emailModelStub = $this->createStub(Email::class);
        $emailModelStub->method('sendEmail')->willThrowException(new \RuntimeException(uniqid()));

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning');

        $sut = $this->getSut(
            emailFactory: $this->emailFactoryReturning($emailModelStub),
            accountTypeResolver: $this->accountResolverReturning($this->account(uniqid() . '@example.com', 1)),
            contentRepository: $contentRepositoryStub,
            language: $this->languageWithAbbr(1, 'en'),
            logger: $loggerMock,
        );

        $sut->notify(affectedUserId: uniqid(), changedAt: new DateTimeImmutable());
    }

    #[Test]
    public function notifyLogsWarningAndSwallowsWhenAccountHasNoEmail(): void
    {
        $emailFactoryMock = $this->createMock(EmailFactoryInterface::class);
        $emailFactoryMock->expects($this->never())->method('create');

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning');

        $sut = $this->getSut(
            emailFactory: $emailFactoryMock,
            accountTypeResolver: $this->accountResolverReturning($this->account('', 1)),
            language: $this->languageWithAbbr(1, 'en'),
            logger: $loggerMock,
        );

        $sut->notify(affectedUserId: uniqid(), changedAt: new DateTimeImmutable());
    }

    #[Test]
    public function notifyLogsWarningAndSwallowsWhenAccountResolutionFails(): void
    {
        $resolverStub = $this->createStub(AccountTypeResolverInterface::class);
        $resolverStub->method('resolveAccount')->willThrowException(new AccountNotFoundException());

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('warning');

        $sut = $this->getSut(
            accountTypeResolver: $resolverStub,
            logger: $loggerMock,
        );

        $sut->notify(affectedUserId: uniqid(), changedAt: new DateTimeImmutable());
    }

    private function account(string $email, int $languageId): AccountDataInterface
    {
        return new AccountData(
            userId: uniqid(),
            email: $email,
            rights: 'user',
            languageId: $languageId,
            shopId: 1,
        );
    }

    private function accountResolverReturning(AccountDataInterface $account): AccountTypeResolverInterface
    {
        $resolverStub = $this->createStub(AccountTypeResolverInterface::class);
        $resolverStub->method('resolveAccount')->willReturn($account);

        return $resolverStub;
    }

    private function emailFactoryReturning(Email $email): EmailFactoryInterface
    {
        $emailFactoryStub = $this->createStub(EmailFactoryInterface::class);
        $emailFactoryStub->method('create')->willReturn($email);

        return $emailFactoryStub;
    }

    /**
     * @param array<string, string> $translations
     */
    private function shopAdapterWith(array $translations): ShopAdapterInterface
    {
        $shopAdapterStub = $this->createStub(ShopAdapterInterface::class);
        $shopAdapterStub->method('translateString')->willReturnCallback(
            fn(string $key): string => $translations[$key] ?? $key
        );

        return $shopAdapterStub;
    }

    private function languageWithAbbr(int $languageId, string $abbr): Language
    {
        $languageStub = $this->createStub(Language::class);
        $languageStub->method('getLanguageAbbr')->willReturnCallback(
            fn(?int $lang = null): string => $lang === $languageId ? $abbr : 'en'
        );

        return $languageStub;
    }

    private function getSut(
        ?EmailFactoryInterface $emailFactory = null,
        ?AccountTypeResolverInterface $accountTypeResolver = null,
        ?PasswordChangeEmailContentRepositoryInterface $contentRepository = null,
        ?PasswordChangeMailRendererInterface $renderer = null,
        ?ShopAdapterInterface $shopAdapter = null,
        ?Language $language = null,
        ?LoggerInterface $logger = null,
    ): PasswordChangeEmailNotifier {
        return new PasswordChangeEmailNotifier(
            emailFactory: $emailFactory ?? $this->createStub(EmailFactoryInterface::class),
            accountTypeResolver: $accountTypeResolver ?? $this->createStub(AccountTypeResolverInterface::class),
            contentRepository: $contentRepository
                ?? $this->createStub(PasswordChangeEmailContentRepositoryInterface::class),
            renderer: $renderer ?? $this->createStub(PasswordChangeMailRendererInterface::class),
            shopAdapter: $shopAdapter ?? $this->createStub(ShopAdapterInterface::class),
            language: $language ?? $this->createStub(Language::class),
            logger: $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
