<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Notifier\Email;

use DateTimeInterface;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailContent;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailRendererInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordChangeEmailContentRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;

class PasswordChangeEmailNotifier implements PasswordChangeEmailNotifierInterface
{
    private const HTML_TEMPLATE = '@oe_security_module/email/html/passwordchange.html.twig';
    private const PLAIN_TEMPLATE = '@oe_security_module/email/plain/passwordchange.html.twig';

    private const SUBJECT_KEY = 'OESM_PASSWORDCHANGE_EMAIL_SUBJECT';
    private const BODY_KEY = 'OESM_PASSWORDCHANGE_EMAIL_BODY';

    private const GERMAN_ABBR = 'de';
    private const GERMAN_FORMAT = 'd.m.Y H:i';
    private const DEFAULT_FORMAT = 'Y-m-d H:i';

    public function __construct(
        private EmailFactoryInterface $emailFactory,
        private AccountTypeResolverInterface $accountTypeResolver,
        private PasswordChangeEmailContentRepositoryInterface $contentRepository,
        private PasswordChangeMailRendererInterface $renderer,
        private ShopAdapterInterface $shopAdapter,
        private Language $language,
        private LoggerInterface $logger,
    ) {
    }

    public function notify(string $affectedUserId, DateTimeInterface $changedAt): void
    {
        try {
            $account = $this->accountTypeResolver->resolveAccount($affectedUserId);
            $email = $account->getEmail();

            if ($email === '') {
                $this->logger->warning(
                    'Skipping the password-change notification: the affected account has no email address.',
                    ['affectedUserId' => $affectedUserId],
                );

                return;
            }

            $languageId = $account->getLanguageId();
            $formattedChangedAt = $this->formatChangedAt($changedAt, $languageId);

            $this->sendInAccountLanguage($email, $languageId, $formattedChangedAt);
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Sending the password-change notification failed.',
                ['affectedUserId' => $affectedUserId, 'exception' => $e::class, 'message' => $e->getMessage()],
            );
        }
    }

    private function sendInAccountLanguage(string $email, int $languageId, string $formattedChangedAt): void
    {
        $previousLanguageId = (int) $this->language->getBaseLanguage();
        $this->language->setTplLanguage($languageId);
        $this->language->setBaseLanguage($languageId);

        try {
            if ($this->sendFromCmsContent($email, $formattedChangedAt)) {
                return;
            }

            $this->sendFallback($email, $formattedChangedAt);
        } finally {
            $this->language->setTplLanguage($previousLanguageId);
            $this->language->setBaseLanguage($previousLanguageId);
        }
    }

    private function sendFromCmsContent(string $email, string $formattedChangedAt): bool
    {
        $subject = $this->contentRepository->getEmailSubject(PasswordChangeMailContent::IDENT);
        if ($subject === null) {
            return false;
        }

        try {
            $data = [
                'subject' => $subject,
                'changedAt' => $formattedChangedAt,
                'contentIdent' => PasswordChangeMailContent::IDENT,
            ];
            $html = $this->renderer->render(self::HTML_TEMPLATE, $data);
            $plain = $this->renderer->render(self::PLAIN_TEMPLATE, $data);
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Rendering the password-change CMS mail template failed; falling back to the built-in mail.',
                [
                    'contentIdent' => PasswordChangeMailContent::IDENT,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ],
            );

            return false;
        }

        if (!str_contains($html, $formattedChangedAt) || !str_contains($plain, $formattedChangedAt)) {
            $this->logger->warning(
                'The password-change CMS mail content does not contain the change timestamp;'
                . ' falling back to the built-in mail.',
                ['contentIdent' => PasswordChangeMailContent::IDENT],
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
                'Sending the password-change CMS mail failed; falling back to the built-in mail.',
                ['contentIdent' => PasswordChangeMailContent::IDENT],
            );

            return false;
        }

        return true;
    }

    private function sendFallback(string $email, string $formattedChangedAt): void
    {
        $subject = $this->shopAdapter->translateString(self::SUBJECT_KEY);
        $bodyTemplate = $this->shopAdapter->translateString(self::BODY_KEY);

        $this->emailFactory->create()->sendEmail($email, $subject, sprintf($bodyTemplate, $formattedChangedAt));
    }

    private function formatChangedAt(DateTimeInterface $changedAt, int $languageId): string
    {
        $format = $this->language->getLanguageAbbr($languageId) === self::GERMAN_ABBR
            ? self::GERMAN_FORMAT
            : self::DEFAULT_FORMAT;

        return $changedAt->format($format);
    }
}
