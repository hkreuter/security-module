<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * @group oe_security_module
 * @group oe_security_module_2fa_settings
 */
final class TwoFactorAuthSettingsCest
{
    private const MODULE_NAME = 'OXID Security Module';
    private const SETTINGS_TAB = 'Settings';

    public function testTwoFactorAuthSettingsAreTranslated(AcceptanceTester $I): void
    {
        $I->wantTo('verify that 2FA module settings are translated in admin');

        $adminPanel = $I->loginAdmin();

        $modulesList = $adminPanel->openModules();
        $modulesList->selectModule(self::MODULE_NAME);
        $modulesList->openModuleTab(self::SETTINGS_TAB);

        $I->selectEditFrame();

        $groupLabel = Translator::translate('SHOP_MODULE_GROUP_two_factor_auth');
        $I->see($groupLabel);
        $I->click($groupLabel);
        $I->waitForText(
            Translator::translate('SHOP_MODULE_oeSecurityTwoFactorAuthEnable')
        );

        $I->see(Translator::translate('SHOP_MODULE_oeSecurityTwoFactorAuthEnable'));
        $I->see(Translator::translate('SHOP_MODULE_oeSecurityTwoFactorAuthOtpLength'));
        $I->see(Translator::translate('SHOP_MODULE_oeSecurityTwoFactorAuthOtpLifetime'));
        $I->see(Translator::translate('SHOP_MODULE_oeSecurityTwoFactorAuthMaxAttempts'));
        $I->see(Translator::translate('SHOP_MODULE_oeSecurityTwoFactorAuthCooldown'));
    }
}
