<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * @group oe_security_module
 * @group oe_security_module_password_reuse_settings
 */
final class PasswordChangeSettingsCest
{
    private string $moduleTitle = 'OXID Security Module';
    private string $settingsTab = 'Settings';
    private string $settingsGroup = 'Password Reuse Prevention';

    private string $reuseEnableField = 'confbools[oeSecurityPasswordReuseEnable]';
    private string $notificationEnableField = 'confbools[oeSecurityPasswordChangeNotificationEnable]';
    private string $customerSizeField = 'confselects[oeSecurityPasswordReuseCustomerSize]';
    private string $adminSizeField = 'confselects[oeSecurityPasswordReuseAdminSize]';

    public function _after(AcceptanceTester $I): void
    {
        $settingService = ContainerFacade::get(ModuleSettingServiceInterface::class);
        $settingService->saveBoolean(ModuleSettingsService::REUSE_PREVENTION_ENABLE, false, Module::MODULE_ID);
        $settingService->saveBoolean(ModuleSettingsService::CHANGE_NOTIFICATION_ENABLE, false, Module::MODULE_ID);
        $settingService->saveString(ModuleSettingsService::CUSTOMER_COLLECTION_SIZE, '5', Module::MODULE_ID);
        $settingService->saveString(ModuleSettingsService::ADMIN_COLLECTION_SIZE, '10', Module::MODULE_ID);
    }

    public function passwordReuseSettingsGroupRendersTranslatedFields(AcceptanceTester $I): void
    {
        $I->wantToTest('the password reuse settings group renders the four settings with translated labels');

        $this->openSettingsGroup($I);

        $I->seeElement("input[name='{$this->reuseEnableField}']");
        $I->seeElement("input[name='{$this->notificationEnableField}']");
        $I->seeElement("select[name='{$this->customerSizeField}']");
        $I->seeElement("select[name='{$this->adminSizeField}']");

        $I->see('Enable password reuse prevention');
        $I->see('Enable password change notification email');
        $I->see('Remembered passwords (customer accounts)');
        $I->see('Remembered passwords (admin accounts)');

        $I->dontSee('SHOP_MODULE_oeSecurityPasswordReuseEnable');
        $I->dontSee('SHOP_MODULE_oeSecurityPasswordChangeNotificationEnable');
        $I->dontSee('SHOP_MODULE_oeSecurityPasswordReuseCustomerSize');
        $I->dontSee('SHOP_MODULE_oeSecurityPasswordReuseAdminSize');
        $I->dontSee('SHOP_MODULE_GROUP_password_reuse');
    }

    public function passwordReuseSettingsPersistChanges(AcceptanceTester $I): void
    {
        $I->wantToTest('editing the password reuse settings persists the new values');

        $this->openSettingsGroup($I);

        $I->checkOption($this->reuseEnableField);
        $I->checkOption($this->notificationEnableField);
        $I->selectOption($this->customerSizeField, '10');
        $I->selectOption($this->adminSizeField, '24');
        $I->click('save');

        $this->openSettingsGroup($I);

        $I->seeCheckboxIsChecked($this->reuseEnableField);
        $I->seeCheckboxIsChecked($this->notificationEnableField);
        $I->seeOptionIsSelected($this->customerSizeField, '10');
        $I->seeOptionIsSelected($this->adminSizeField, '24');
    }

    private function openSettingsGroup(AcceptanceTester $I): void
    {
        $module = $I->loginAdmin()
            ->openModules()
            ->selectModule($this->moduleTitle);
        $module->openModuleTab($this->settingsTab);

        $I->click($this->settingsGroup);
        $I->waitForElement("input[name='{$this->reuseEnableField}']", 10);
    }
}
