<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service;

interface AdminTwoFaLoginServiceInterface
{
    /**
     * Stores the pending admin user in the session under the admin-specific key
     * and triggers OTP delivery.
     */
    public function startChallenge(string $userId): void;

    /**
     * Removes the pending-user session key, calls the login adapter to set the
     * admin session, invalidates the OTP challenge (in a finally block), and
     * redirects the browser to admin_start.
     */
    public function completeLogin(string $userId): void;

    /**
     * Removes the pending-user session key, invalidates the OTP challenge (when
     * a userId is available), and redirects the browser back to the admin login
     * page.
     */
    public function abandonLogin(?string $userId): void;
}
