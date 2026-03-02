<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Model;

use OxidEsales\Eshop\Core\Exception\InputException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorAuthRequiredException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface as TwoFactorAuthSettingsInterface;
use OxidEsales\SecurityModule\Captcha\Captcha\Image\Exception\CaptchaValidateException as ImageCaptchaException;
use OxidEsales\SecurityModule\Captcha\Captcha\HoneyPot\Exception\CaptchaValidateException as HoneyPotCaptchaException;
use OxidEsales\SecurityModule\Captcha\Service\CaptchaServiceInterface;
use OxidEsales\SecurityModule\Captcha\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Shared\Core\InputValidator;

/**
 * User model extended
 *
 * @mixin \OxidEsales\Eshop\Application\Model\User
 * @eshopExtension
 */
class User extends User_parent implements User2FAInterface
{
    public function checkValues($sLogin, $sPassword, $sPassword2, $aInvAddress, $aDelAddress): void
    {
        if ($this->isCaptchaEnabled() && $this->shouldValidateCaptcha()) {
            /** @var InputValidator $oInputValidator */
            $oInputValidator = Registry::getInputValidator();
            $captchaService = $this->getService(CaptchaServiceInterface::class);

            try {
                $captchaService->validate(
                    Registry::getRequest()
                );
            } catch (ImageCaptchaException $e) {
                $oInputValidator->addValidationError(
                    "captcha",
                    oxNew(
                        InputException::class,
                        Registry::getLang()->translateString($e->getMessage())
                    )
                );
            } catch (HoneyPotCaptchaException $e) {
                throw $e;
            }
        }

        parent::checkValues($sLogin, $sPassword, $sPassword2, $aInvAddress, $aDelAddress);
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function login($userName, $password, $setSessionCookie = false): bool
    {
        if (!$this->isCaptchaEnabled()) {
            return parent::login($userName, $password, $setSessionCookie);
        }

        if (!$this->isAdmin()) {
            $captchaService = $this->getService(CaptchaServiceInterface::class);

            try {
                $captchaService->validate(
                    Registry::getRequest()
                );
            } catch (ImageCaptchaException | HoneyPotCaptchaException $e) {
                throw oxNew(UserException::class, $e->getMessage());
            }
        }

        return parent::login($userName, $password, $setSessionCookie);
    }

    private function isCaptchaEnabled(): bool
    {
        $settingsService = $this->getService(ModuleSettingsServiceInterface::class);
        return $settingsService->isCaptchaEnabled() || $settingsService->isHoneyPotCaptchaEnabled();
    }

    protected function shouldValidateCaptcha(): bool
    {
        return !$this->getUser();
    }

    public function is2FAEnabled(): bool
    {
        return (bool) $this->getFieldData('oesm2faenabled');
    }

    public function set2FAEnabled(bool $enabled): void
    {
        $this->assign(['oesm2faenabled' => $enabled ? 1 : 0]);
    }

    protected function onLogin($userName, $password)
    {
        parent::onLogin($userName, $password);

        if (!$this->isLoaded()) {
            return;
        }

        $settings = $this->getService(TwoFactorAuthSettingsInterface::class);

        if (!$settings->isTwoFactorAuthEnabled()) {
            return;
        }

        if (!$this->is2FAEnabled()) {
            return;
        }

        throw new TwoFactorAuthRequiredException($this->getId());
    }
}
