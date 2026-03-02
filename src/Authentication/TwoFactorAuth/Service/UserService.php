<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Shared\Model\User2FAInterface;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly ModuleSettingsServiceInterface $moduleSettingsService,
    ) {
    }

    public function requiresTwoFactorAuth(User2FAInterface $user): bool
    {
        if (!$this->moduleSettingsService->isTwoFactorAuthEnabled()) {
            return false;
        }

        return $user->is2FAEnabled();
    }
}
