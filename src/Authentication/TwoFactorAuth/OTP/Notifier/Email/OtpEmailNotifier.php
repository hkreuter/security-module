<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\Email;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Email\OtpMailContent;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Email\OtpMailRendererInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OtpEmailContentRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\UserRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\OtpNotifierInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;

class OtpEmailNotifier implements OtpNotifierInterface
{
    private const HTML_TEMPLATE = '@oe_security_module/email/html/twofactorotp.html.twig';
    private const PLAIN_TEMPLATE = '@oe_security_module/email/plain/twofactorotp.html.twig';

    public function __construct(
        private EmailFactoryInterface $emailFactory,
        private UserRepositoryInterface $userRepository,
        private ShopAdapterInterface $shopAdapter,
        private OtpEmailContentRepositoryInterface $contentRepository,
        private OtpMailRendererInterface $renderer,
        private TwoFAShopSettingsInterface $settings,
        private LoggerInterface $logger,
    ) {
    }

    public function notify(string $userId, #[\SensitiveParameter] string $code): void
    {
        $email = $this->userRepository->getUserById($userId)->getEmail();
        $minutes = $this->otpLifetimeInMinutes();

        if ($this->sendFromCmsContent($email, $code, $minutes)) {
            return;
        }

        $this->sendFallback($email, $code, $minutes);
    }

    private function sendFromCmsContent(string $email, #[\SensitiveParameter] string $code, int $minutes): bool
    {
        $subject = $this->contentRepository->getEmailSubject(OtpMailContent::IDENT);
        if ($subject === null) {
            return false;
        }

        try {
            $data = [
                'otp' => $code,
                'minutes' => $minutes,
                'subject' => $subject,
                'contentIdent' => OtpMailContent::IDENT,
            ];
            $html = $this->renderer->render(self::HTML_TEMPLATE, $data);
            $plain = $this->renderer->render(self::PLAIN_TEMPLATE, $data);
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Rendering the 2FA OTP CMS mail template failed; falling back to the built-in mail.',
                ['contentIdent' => OtpMailContent::IDENT, 'exception' => $e::class, 'message' => $e->getMessage()],
            );

            return false;
        }

        if (!str_contains($html, $code) || !str_contains($plain, $code)) {
            $this->logger->warning(
                'The 2FA OTP CMS mail content does not contain the code placeholder;'
                . ' falling back to the built-in mail.',
                ['contentIdent' => OtpMailContent::IDENT],
            );

            return false;
        }

        $mail = $this->emailFactory->create();
        $shop = $mail->getShop();

        $mail->setFrom((string) $shop->getFieldData('oxorderemail'), (string) $shop->getFieldData('oxname'));
        $mail->setSmtp($shop);
        $mail->setSubject($subject);
        $mail->setBody($html);
        $mail->setAltBody($plain);
        $mail->setRecipient($email, '');

        if (!$mail->send()) {
            $this->logger->warning(
                'Sending the 2FA OTP CMS mail failed; falling back to the built-in mail.',
                ['contentIdent' => OtpMailContent::IDENT],
            );

            return false;
        }

        return true;
    }

    private function sendFallback(string $email, #[\SensitiveParameter] string $code, int $minutes): void
    {
        $subject = $this->shopAdapter->translateString('OTP_EMAIL_SUBJECT');
        $bodyTemplate = $this->shopAdapter->translateString('OTP_EMAIL_BODY');

        $this->emailFactory->create()->sendEmail($email, $subject, sprintf($bodyTemplate, $code, $minutes));
    }

    private function otpLifetimeInMinutes(): int
    {
        return max(1, (int) round($this->settings->getOtpCodeLifetime() / 60));
    }
}
