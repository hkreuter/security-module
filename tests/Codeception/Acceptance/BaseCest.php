<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use Codeception\Util\Fixtures;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettings;
use OxidEsales\SecurityModule\Captcha\Service\ModuleSettingsService as CaptchaModuleSettingsService;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\FormSecurity\Service\ModuleSettingsService as FormSecurityModuleSettings;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;
use OxidEsales\SecurityModule\PasswordPolicy\Service\ModuleSettingsServiceInterface as PasswordSettingsServiceInterface;

abstract class BaseCest
{
    protected function getExistingUserData()
    {
        return Fixtures::get('existingUser');
    }

    protected function getNewUserData()
    {
        return Fixtures::get('newUser');
    }

    protected function setPasswordState(bool $state)
    {
        ContainerFacade::get(PasswordSettingsServiceInterface::class)->saveIsPasswordPolicyEnabled($state);
    }

    protected function setCaptchaState(bool $state): void
    {
        ContainerFacade::get(ModuleSettingServiceInterface::class)
            ->saveBoolean(CaptchaModuleSettingsService::CAPTCHA_ENABLE, $state, Module::MODULE_ID);
    }

    protected function setHoneyPotCaptchaState(bool $state): void
    {
        ContainerFacade::get(ModuleSettingServiceInterface::class)
            ->saveBoolean(CaptchaModuleSettingsService::HONEYPOT_CAPTCHA_ENABLE, $state, Module::MODULE_ID);
    }

    protected function setTwoFactorAuthState(bool $state)
    {
        ContainerFacade::get(ModuleSettingServiceInterface::class)->saveBoolean(
            TwoFAShopSettings::ACTIVE,
            $state,
            Module::MODULE_ID
        );
    }

    protected function setGetFormStripStokenState(bool $state): void
    {
        ContainerFacade::get(ModuleSettingServiceInterface::class)->saveBoolean(
            FormSecurityModuleSettings::GET_FORM_STRIP_STOKEN,
            $state,
            Module::MODULE_ID
        );
    }

    protected function setUserTwoFAState(AcceptanceTester $I, bool $state): void
    {
        $userData = $this->getExistingUserData();
        $I->updateInDatabase(
            'oxuser',
            ['OE2FAENABLED' => (int) $state],
            ['OXID' => $userData['userId']]
        );
    }
}
