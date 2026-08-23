<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Authentication\TwoFactorAuth\OTP\Notifier\Email;

use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\UserInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Email\OtpMailContent;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Email\OtpMailRenderer;
use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\EmailFactory;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OtpEmailContentRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\UserRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\Email\OtpEmailNotifier;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
use Psr\Log\NullLogger;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class OtpEmailNotifierTest extends IntegrationTestCase
{
    #[Test]
    public function notifySendsHtmlMailWithTheCodeThroughShopMailer(): void
    {
        $recipient = 'otp_' . substr(uniqid('', true), 0, 12) . '@example.test';
        $code = (string) random_int(100000, 999999);
        $this->seedActiveContent(OtpMailContent::IDENT, 'Your verification code is {{ otp }}.');

        $this->getSut($recipient)->notify(userId: uniqid(), code: $code);

        $message = $this->fetchDeliveredMessage($recipient);
        $this->assertNotNull($message, 'No email was delivered to the recipient.');
        $this->assertStringContainsString($code, (string) $message['HTML']);
    }

    private function getSut(string $recipient): OtpEmailNotifier
    {
        $userStub = $this->createStub(UserInterface::class);
        $userStub->method('getEmail')->willReturn($recipient);
        $userRepositoryStub = $this->createStub(UserRepositoryInterface::class);
        $userRepositoryStub->method('getUserById')->willReturn($userStub);

        return new OtpEmailNotifier(
            emailFactory: new EmailFactory(),
            userRepository: $userRepositoryStub,
            shopAdapter: $this->get(ShopAdapterInterface::class),
            contentRepository: $this->get(OtpEmailContentRepositoryInterface::class),
            renderer: new OtpMailRenderer(
                ContainerFactory::getInstance()->getContainer()->get(TemplateRendererBridgeInterface::class),
            ),
            settings: $this->settingsStub(),
            logger: new NullLogger(),
        );
    }

    private function settingsStub(): TwoFAShopSettingsInterface
    {
        $stub = $this->createStub(TwoFAShopSettingsInterface::class);
        $stub->method('getOtpCodeLifetime')->willReturn(300);

        return $stub;
    }

    private function seedActiveContent(string $ident, string $body): void
    {
        // Idempotent seed: remove any row left behind by a previous run before inserting.
        DatabaseProvider::getDb()->execute('DELETE FROM oxcontents WHERE OXLOADID = ?', [$ident]);

        $content = oxNew(Content::class);
        $content->assign([
            'oxloadid' => $ident,
            'oxactive' => 1,
            'oxshopid' => 1,
            'oxsnippet' => 1,
            'oxtype' => 0,
            'oxtitle' => 'Your verification code',
            'oxcontent' => $body,
        ]);
        $content->save();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchDeliveredMessage(string $recipient): ?array
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $list = $this->mailpitGet('/search?query=' . urlencode('to:' . $recipient));
            if (!empty($list['messages'])) {
                $id = $list['messages'][0]['ID'];

                return $this->mailpitGet('/message/' . $id);
            }
            usleep(100000);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function mailpitGet(string $path): array
    {
        $response = file_get_contents($this->mailpitApiBase() . $path);

        return $response ? (array) json_decode($response, true) : [];
    }

    private function mailpitApiBase(): string
    {
        $host = getenv('MAIL_HOST') ?: 'mailpit';
        $port = getenv('MAIL_WEB_PORT') ?: '8025';

        return sprintf('http://%s:%s/api/v1', $host, $port);
    }
}
