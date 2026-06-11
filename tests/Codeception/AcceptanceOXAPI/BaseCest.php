<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\AcceptanceOXAPI;

use Codeception\Util\Fixtures;
use Lcobucci\JWT\Token\DataSet;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettings;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

abstract class BaseCest
{
    protected function user(): array
    {
        return Fixtures::get('existingUser');
    }

    /**
     * Shop-level 2FA on + this user flagged 2FA + a clean OTP slot and inbox, so a login triggers a
     * fresh challenge whose OTP we then read out of Mailpit.
     */
    protected function prepareTwoFAChallengeUser(AcceptanceTester $I): void
    {
        $this->setShopTwoFactorAuth(true);
        $this->setUserTwoFA($I, true);
        $this->resetOtpState($I);
        $I->deleteAllEmails();
    }

    /**
     * Shop-level 2FA on, but THIS user not enrolled — a normal login must return a full token, proving
     * the SecureApiLegacy/subscriber/refresh decorators leave non-2FA logins untouched.
     */
    protected function prepareRegularUser(AcceptanceTester $I): void
    {
        $this->setShopTwoFactorAuth(true);
        $this->setUserTwoFA($I, false);
        $this->resetOtpState($I);
        $I->deleteAllEmails();
    }

    protected function setShopTwoFactorAuth(bool $state): void
    {
        ContainerFacade::get(ModuleSettingServiceInterface::class)
            ->saveBoolean(TwoFAShopSettings::ACTIVE, $state, Module::MODULE_ID);
    }

    protected function setUserTwoFA(AcceptanceTester $I, bool $state): void
    {
        $I->updateInDatabase('oxuser', ['OE2FAENABLED' => (int) $state], ['OXID' => $this->user()['userId']]);
    }

    protected function resetOtpState(AcceptanceTester $I): void
    {
        $I->deleteFromDatabase('oesm_2fa_otp', ['OXUSERID' => $this->user()['userId']]);
        $I->deleteFromDatabase('oegraphqltoken', ['OXUSERID' => $this->user()['userId']]);
    }

    /**
     * Read the real 6-digit OTP from the verification email Mailpit captured (OtpEmailNotifier sends
     * "...verification code...: NNNNNN"). Language-neutral: the email language follows the shop
     * default, so we match on the digits, not the localized wording.
     */
    protected function grabOtpFromEmail(AcceptanceTester $I): string
    {
        $I->openRecentEmail();
        $body = $I->grabTextBodyFromEmail();

        $matched = preg_match('/(\d{6})/', $body, $matches);
        $I->assertSame(1, $matched, 'OTP email did not contain a 6-digit code');

        return $matches[1];
    }

    protected function sendGraphQL(AcceptanceTester $I, string $query, array $variables = []): array
    {
        $I->sendGQLQuery($query, $variables);
        $I->seeResponseIsJson();

        return $I->grabJsonResponseAsArray();
    }

    protected function claimsOf(AcceptanceTester $I, string $jwt): DataSet
    {
        return $I->parseJwt($jwt)->claims();
    }
}
