<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Controller;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\SecurityModule\Captcha\Service\CaptchaServiceInterface;
use OxidEsales\SecurityModule\Captcha\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Factory\UserModelFactoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;

/**
 * @mixin \OxidEsales\Eshop\Application\Controller\ForgotPasswordController
 * @eshopExtension
 */
class ForgotPasswordController extends ForgotPasswordController_parent
{
    public function forgotPassword(): ?bool
    {
        $settingsService = $this->getService(ModuleSettingsServiceInterface::class);
        if (!$settingsService->isCaptchaEnabled() && !$settingsService->isHoneyPotCaptchaEnabled()) {
            return parent::forgotPassword();
        }

        $captchaService = $this->getService(CaptchaServiceInterface::class);

        try {
            $captchaService->validate(
                Registry::getRequest()
            );
        } catch (StandardException $e) {
            Registry::getUtilsView()->addErrorToDisplay($e->getMessage());
            return false;
        }

        return parent::forgotPassword();
    }

    public function updatePassword()
    {
        $candidate = (string)Registry::getRequest()->getRequestParameter('password_new');
        if ($candidate === '') {
            return parent::updatePassword();
        }

        $user = $this->getService(UserModelFactoryInterface::class)->create();
        if (!$user->loadUserByUpdateId($this->getUpdateId())) {
            return parent::updatePassword();
        }

        $userId = (string)$user->getId();
        $currentHash = (string)$this->getService(StoredPasswordReaderInterface::class)
            ->getStoredPasswordHash($userId);

        try {
            $this->getService(PasswordReuseGuardServiceInterface::class)
                ->guardChange($userId, $candidate, $currentHash);
        } catch (PasswordReuseException $exception) {
            return Registry::getUtilsView()->addErrorToDisplay(PasswordReuseException::MESSAGE_KEY, false, true);
        } catch (PasswordReuseCheckException $exception) {
            return Registry::getUtilsView()->addErrorToDisplay(PasswordReuseCheckException::MESSAGE_KEY, false, true);
        }

        $this->getService(ConfirmedChangeRegistryInterface::class)->confirm($userId);

        return parent::updatePassword();
    }
}
