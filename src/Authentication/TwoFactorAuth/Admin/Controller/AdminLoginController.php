<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service\AdminTwoFaLoginServiceInterface;

/**
 * Thin wrapper around the admin LoginController.
 *
 * After a successful credential check (parent returns 'admin_start') this
 * controller checks whether admin 2FA is required. If so it:
 *   1. Removes 'auth' from the session (login is not yet complete),
 *   2. Starts the OTP challenge (stores pending user, sends code), and
 *   3. Returns the admin OTP form controller key.
 *
 * Failed logins (parent returns null) are passed through unchanged — no
 * return-type constraint on checklogin() so no TypeError can occur.
 *
 * No constructor injection — OXID's module chain requires a no-arg
 * (or transparent) constructor for the LoginController parent.
 *
 * @mixin \OxidEsales\Eshop\Application\Controller\Admin\LoginController
 * @eshopExtension
 */
class AdminLoginController extends AdminLoginController_parent
{
    public function checklogin()
    {
        $result = parent::checklogin();

        if ($result !== 'admin_start' || !$this->isAdminTwoFAEnabled()) {
            return $result;
        }

        $userId = $this->getAndClearAdminUserId();
        $this->startAdminChallenge($userId);

        return 'oesm_admin_twofactorauth';
    }

    /**
     * Extracted to allow mocking of ContainerFacade::getParameter() in unit tests.
     */
    protected function isAdminTwoFAEnabled(): bool
    {
        return (bool) ContainerFacade::getParameter('oe_security.admin_2fa_enabled');
    }

    /**
     * Returns the authenticated user ID from the session and clears the
     * 'auth' variable — 2FA has not been completed yet.
     *
     * Extracted to allow mocking in unit tests.
     */
    protected function getAndClearAdminUserId(): string
    {
        $session = Registry::getSession();
        $userId = (string) $session->getVariable('auth');
        $session->deleteVariable('auth');
        return $userId;
    }

    /**
     * Stores the pending admin user in the admin-specific session key and
     * triggers OTP delivery via AdminTwoFaLoginService.
     *
     * Extracted to allow mocking in unit tests.
     */
    protected function startAdminChallenge(string $userId): void
    {
        $service = ContainerFacade::get(AdminTwoFaLoginServiceInterface::class);
        assert($service instanceof AdminTwoFaLoginServiceInterface);
        $service->startChallenge($userId);
    }
}
