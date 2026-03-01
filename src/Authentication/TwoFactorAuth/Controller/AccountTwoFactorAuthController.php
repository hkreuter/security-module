<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\UserServiceInterface;

class AccountTwoFactorAuthController extends AccountController
{
    protected $_sThisTemplate = 'modules/oe/security-module/page/account/twofactorauth';

    private bool $statusChanged = false;

    public function render(): string
    {
        parent::render();

        $user = $this->getUser();
        if (!$user) {
            return $this->_sThisTemplate = $this->_sThisLoginTemplate;
        }

        return $this->_sThisTemplate;
    }

    public function enableTwoFactorAuth(): void
    {
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        $userService = $this->getService(UserServiceInterface::class);
        $userService->setUserTwoFactorEnabled($user->getId(), true);
        $this->statusChanged = true;
    }

    public function disableTwoFactorAuth(): void
    {
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        $userService = $this->getService(UserServiceInterface::class);
        $userService->setUserTwoFactorEnabled($user->getId(), false);
        $this->statusChanged = true;
    }

    public function isTwoFactorEnabled(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $userService = $this->getService(UserServiceInterface::class);
        return $userService->isUserTwoFactorEnabled($user->getId());
    }

    public function isTwoFactorGloballyEnabled(): bool
    {
        $settings = $this->getService(ModuleSettingsServiceInterface::class);
        return $settings->isTwoFactorAuthEnabled();
    }

    public function isStatusChanged(): bool
    {
        return $this->statusChanged;
    }
}
