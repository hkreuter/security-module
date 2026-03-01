<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;

class EmailAdapter implements EmailAdapterInterface
{
    public const HTML_TEMPLATE = '@oe_security_module/email/html/otp_verification.html.twig';
    public const PLAIN_TEMPLATE = '@oe_security_module/email/plain/otp_verification.html.twig';

    public function __construct(
        private readonly EmailFactoryInterface $emailFactory,
        private readonly ModuleSettingsServiceInterface $moduleSettingsService,
        private readonly TemplateRendererBridgeInterface $templateRendererBridge,
    ) {
    }

    public function send(string $email, string $code): void
    {
        $lifetimeMinutes = (int) ceil($this->moduleSettingsService->getOtpLifetime() / 60);

        $emailObj = $this->emailFactory->create();
        $emailObj->setViewData('otpCode', $code);
        $emailObj->setViewData('lifetimeMinutes', $lifetimeMinutes);
        $emailObj->processViewArray();

        $renderer = $this->templateRendererBridge->getTemplateRenderer();
        $viewData = $emailObj->getViewData();

        $emailObj->setBody(
            $renderer->renderTemplate(self::HTML_TEMPLATE, $viewData),
        );
        $emailObj->setAltBody(
            $renderer->renderTemplate(self::PLAIN_TEMPLATE, $viewData),
        );

        $shop = $emailObj->getShop();
        $emailObj->setFrom(
            $shop->getFieldData('oxinfoemail'),
            $shop->getFieldData('oxname'),
        );
        $emailObj->setRecipient($email);
        $emailObj->setReplyTo(
            $shop->getFieldData('oxorderemail'),
            $shop->getFieldData('oxname'),
        );

        if (!$emailObj->send()) {
            throw new \RuntimeException('Failed to send OTP email');
        }
    }
}
