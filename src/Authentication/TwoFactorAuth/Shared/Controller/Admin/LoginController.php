<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Shared\Controller\Admin;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorAuthRequiredException;

/**
 * @mixin \OxidEsales\Eshop\Application\Controller\Admin\LoginController
 * @eshopExtension
 */
class LoginController extends LoginController_parent
{
    private string $twoFactorUserId = '';

    public function checklogin()
    {
        try {
            return parent::checklogin();
        } catch (TwoFactorAuthRequiredException $e) {
            $this->twoFactorUserId = $e->getUserId();
            return 'oe_security_2fa_admin';
        }
    }

    public function getTwoFactorUserId(): string
    {
        return $this->twoFactorUserId;
    }
}
