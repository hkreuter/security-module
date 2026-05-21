<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Page\Account\UserLogin;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * @group oe_security_module
 * @group oe_security_module_form_security
 */
class GetFormSecurityCest extends BaseCest
{
    private const SEARCH_TERM = '1000';
    private const CATEGORY = 'Test category 0 [EN] šÄßüл';
    private const ATTRIBUTE_NAME = 'Test attribute 1 [EN] šÄßüл';
    private const ATTRIBUTE_VALUE = 'attr value 1 [EN] šÄßüл';

    public function _before(AcceptanceTester $I): void
    {
        $this->setCaptchaState(false);
        $this->setPasswordState(false);
        $this->setGetFormStripStokenState(false);
        $this->loginUser($I);
    }

    private function loginUser(AcceptanceTester $I): void
    {
        $userData = $this->getExistingUserData();
        $loginPage = new UserLogin($I);
        $I->amOnPage($loginPage->URL);
        $loginPage->login($userData['userLoginName'], $userData['userPassword']);
    }

    public function _after(AcceptanceTester $I): void
    {
        $this->setGetFormStripStokenState(false);
    }

    public function testSearchFormFunctionsByDefault(AcceptanceTester $I): void
    {
        $I->wantToTest('search form works and includes stoken in URL when setting is disabled');

        $I->openShop()
            ->searchFor(self::SEARCH_TERM)
            ->seeSearchCount(1);

        $I->seeInCurrentUrl('stoken=');
    }

    public function testSearchFormStripsStokenWhenSettingIsEnabled(AcceptanceTester $I): void
    {
        $I->wantToTest('search form works and stoken is absent from URL when setting is enabled');

        $this->setGetFormStripStokenState(true);

        $I->openShop()
            ->searchFor(self::SEARCH_TERM)
            ->seeSearchCount(1);

        $I->dontSeeInCurrentUrl('stoken=');
    }

    public function testAttributeFilterFunctionsByDefault(AcceptanceTester $I): void
    {
        $I->wantToTest('attribute filter works and includes stoken in URL when setting is disabled');

        $I->openShop()
            ->openCategoryPage(self::CATEGORY)
            ->selectFilter(self::ATTRIBUTE_NAME, self::ATTRIBUTE_VALUE);

        $I->seeInCurrentUrl('stoken=');
    }

    public function testAttributeFilterStripsStokenWhenSettingIsEnabled(AcceptanceTester $I): void
    {
        $I->wantToTest('attribute filter works and stoken is absent from URL when setting is enabled');

        $this->setGetFormStripStokenState(true);

        $I->openShop()
            ->openCategoryPage(self::CATEGORY)
            ->selectFilter(self::ATTRIBUTE_NAME, self::ATTRIBUTE_VALUE);

        $I->dontSeeInCurrentUrl('stoken=');
    }

    public function testResetFilterFunctionsByDefault(AcceptanceTester $I): void
    {
        $I->wantToTest('reset filter works and includes stoken in URL when setting is disabled');

        $I->openShop()
            ->openCategoryPage(self::CATEGORY)
            ->selectFilter(self::ATTRIBUTE_NAME, self::ATTRIBUTE_VALUE)
            ->resetFilter();

        $I->seeInCurrentUrl('stoken=');
    }

    public function testResetFilterStripsStokenWhenSettingIsEnabled(AcceptanceTester $I): void
    {
        $I->wantToTest('reset filter works and stoken is absent from URL when setting is enabled');

        $this->setGetFormStripStokenState(true);

        $I->openShop()
            ->openCategoryPage(self::CATEGORY)
            ->selectFilter(self::ATTRIBUTE_NAME, self::ATTRIBUTE_VALUE)
            ->resetFilter();

        $I->dontSeeInCurrentUrl('stoken=');
    }
}
