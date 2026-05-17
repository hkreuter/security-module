<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Framework\Session\SessionInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service\AdminTwoFaLoginService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service\AdminTwoFaLoginServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\CodeValidationException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\AuthCodeRequestInterface;

class AdminTwoFactorAuthController extends AdminController
{
    public function __construct(
        private readonly TwoFAServiceInterface $twoFAService,
        private readonly AdminTwoFaLoginServiceInterface $adminLoginService,
        private readonly SessionInterface $session,
        private readonly AuthCodeRequestInterface $authCodeRequest,
        private readonly UtilsView $utilsView,
        private readonly Utils $utils,
    ) {
        $this->initParent();
    }

    /**
     * Always allow access — the admin session check ('auth') is absent by
     * design during the 2FA challenge, so we must bypass AdminController's
     * default authorization guard here.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function render(): string
    {
        $userId = $this->session->get(AdminTwoFaLoginService::ADMIN_SESSION_KEY);

        if (!$userId) {
            $this->utils->redirect('index.php?cl=login', true, 302);
            // Only reached in test context — redirect() calls exit() in production.
            return '@oe_security_module/admin/admin_two_factor_auth';
        }

        if ($this->twoFAService instanceof TwoFAResendableInterface) {
            $this->addTplParam('resendable', true);
            $this->addTplParam('remainingAttempts', $this->twoFAService->getRemainingAttempts($userId));
            $this->addTplParam('resendCooldownRemaining', $this->twoFAService->getCooldownRemaining($userId));
        }

        return '@oe_security_module/admin/admin_two_factor_auth';
    }

    public function verifyCode(): ?string
    {
        $userId = $this->session->get(AdminTwoFaLoginService::ADMIN_SESSION_KEY);

        if (!$userId) {
            $this->utils->redirect('index.php?cl=login', true, 302);
            // Only reached in test context — redirect() calls exit() in production.
            return null;
        }

        try {
            $this->twoFAService->verify($userId, $this->authCodeRequest->getCode());
            $this->adminLoginService->completeLogin($userId);
        } catch (CodeValidationException $e) {
            $this->utilsView->addErrorToDisplay($e);
        }

        return null;
    }

    public function abandonChallenge(): void
    {
        $this->adminLoginService->abandonLogin(
            $this->session->get(AdminTwoFaLoginService::ADMIN_SESSION_KEY)
        );
    }

    /**
     * Extracted to allow mocking of AdminController::__construct() in unit
     * tests (AdminController calls Registry::getConfig() which is unavailable
     * outside the full OXID bootstrap).
     */
    protected function initParent(): void
    {
        parent::__construct();
    }
}
