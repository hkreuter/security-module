<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Shared\Component;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorAuthRequiredException;

/**
 * @mixin \OxidEsales\Eshop\Application\Component\UserComponent
 * @eshopExtension
 */
class UserComponent extends UserComponent_parent
{
    private bool $twoFactorAuthRequired = false;
    private string $twoFactorUserId = '';
    private string $twoFactorContext = '';

    public function login()
    {
        try {
            return parent::login();
        } catch (TwoFactorAuthRequiredException $e) {
            $this->twoFactorAuthRequired = true;
            $this->twoFactorUserId = $e->getUserId();
            $this->twoFactorContext = $e->getContext();

            return null;
        }
    }

    public function isTwoFactorAuthRequired(): bool
    {
        return $this->twoFactorAuthRequired;
    }

    public function getTwoFactorUserId(): string
    {
        return $this->twoFactorUserId;
    }

    public function getTwoFactorContext(): string
    {
        return $this->twoFactorContext;
    }
}
