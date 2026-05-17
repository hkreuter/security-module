<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * Acceptance tests for admin two-factor authentication.
 *
 * These tests require the DI parameter `oe_security.admin_2fa_enabled`
 * to be `true` at container compile time. Set it via:
 *   - Env var: OE_SECURITY_ADMIN_2FA_ENABLED=1
 *   - Or: var/configuration/configurable_services.yaml
 *
 * @group oe_security_module
 * @group oe_security_module_admin_two_fa
 */
class AdminTwoFAAuthenticationCest extends BaseCest
{
    private const ADMIN_URL = '/admin/';
    private const ADMIN_USER = 'noreply@oxid-esales.com';
    private const ADMIN_PASSWORD = 'admin';
    private const ADMIN_USER_ID = 'oxdefaultadmin';

    private const KNOWN_CODE = '123456';

    private string $otpInput = '#auth_code';

    public function _before(AcceptanceTester $I): void
    {
        $this->clearAdminOtpState($I);
    }

    public function _after(AcceptanceTester $I): void
    {
        $this->clearAdminOtpState($I);
    }

    private function clearAdminOtpState(AcceptanceTester $I): void
    {
        $I->deleteFromDatabase('oesm_2fa_otp', ['OXUSERID' => self::ADMIN_USER_ID]);
    }

    /**
     * Navigates to admin, enters credentials, and submits the login form.
     * Expects to land on the 2FA OTP page.
     */
    private function loginToAdminAndTriggerOtp(AcceptanceTester $I): void
    {
        $I->amOnPage(self::ADMIN_URL);
        $I->waitForPageLoad();
        $I->fillField('#usr', self::ADMIN_USER);
        $I->fillField('#pwd', self::ADMIN_PASSWORD);
        $I->click('#login [type="submit"]');
        $I->waitForPageLoad();
    }

    public function testAdminLoginWith2FAEnabledShowsOtpForm(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);

        $I->seeElement($this->otpInput);
        $I->seeInCurrentUrl('oesm_admin_twofactorauth');
    }

    public function testNavigatingToAdminStartDuringChallengeRedirectsToLogin(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);
        $I->seeElement($this->otpInput);

        $I->amOnPage('/admin/?cl=admin_start');
        $I->waitForPageLoad();

        // No 'auth' in session — OXID redirects to the admin login page
        $I->seeInCurrentUrl('cl=login');
        $I->seeElement('#usr');
        $I->dontSeeElement($this->otpInput);
    }

    public function testInvalidCodeShowsError(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);

        $I->fillField($this->otpInput, '000000');
        $I->submitForm('form[name="admin_2fa_verify"]', []);
        $I->waitForPageLoad();

        $I->see(Translator::translate('ERROR_INVALID_CODE'));
        $I->seeElement($this->otpInput);
    }

    public function testWrongCodeDecreasesRemainingAttempts(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);

        $attemptsBefore = (int) $I->grabTextFrom('#remaining-attempts');

        $I->fillField($this->otpInput, '000000');
        $I->submitForm('form[name="admin_2fa_verify"]', []);
        $I->waitForPageLoad();

        $I->see(Translator::translate('ERROR_INVALID_CODE'));
        $I->see((string) ($attemptsBefore - 1), '#remaining-attempts');
    }

    public function testResendButtonIsDisabledOnOtpPageLoad(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);

        // Code was just sent at login — server-driven cooldown should disable the button
        $I->waitForJS("return document.getElementById('resend-btn').disabled === true", 5);
        $I->seeElement('#resend-btn[disabled]');
    }

    public function testAbandonChallengeRedirectsToAdminLogin(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);

        $I->submitForm('form[name="admin_2fa_abandon"]', []);
        $I->waitForPageLoad();

        $I->seeInCurrentUrl('cl=login');
        $I->seeElement('#usr');
        $I->dontSeeElement($this->otpInput);
        $I->dontSeeInDatabase('oesm_2fa_otp', ['OXUSERID' => self::ADMIN_USER_ID]);
    }

    public function testAfterExhaustedAttemptsOnlyAbandonIsShown(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);

        for ($i = 0; $i < 5; $i++) {
            $I->fillField($this->otpInput, '000000');
            $I->submitForm('form[name="admin_2fa_verify"]', []);
            $I->waitForPageLoad();
        }

        $I->dontSeeElement($this->otpInput);
        $I->dontSeeElement('form[name="admin_2fa_verify"]');
        $I->seeElement('form[name="admin_2fa_abandon"]');
    }

    public function testValidOtpCompletesAdminLogin(AcceptanceTester $I): void
    {
        $this->loginToAdminAndTriggerOtp($I);

        // Backdoor: replace the stored hash with sha256 of our known code
        $I->updateInDatabase(
            'oesm_2fa_otp',
            ['CODE_HASH' => hash('sha256', self::KNOWN_CODE)],
            ['OXUSERID' => self::ADMIN_USER_ID]
        );

        $I->fillField($this->otpInput, self::KNOWN_CODE);
        $I->submitForm('form[name="admin_2fa_verify"]', []);
        $I->waitForPageLoad();

        $I->seeInCurrentUrl('admin_start');
        $I->dontSee('Authorization error occurred!');
        $I->dontSeeElement($this->otpInput);
        $I->dontSeeInDatabase('oesm_2fa_otp', ['OXUSERID' => self::ADMIN_USER_ID]);
    }
}
