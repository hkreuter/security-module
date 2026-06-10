<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\AcceptanceOXAPI;

use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * The access+refresh flow: login() -> challenge token with an EMPTY refresh slot (Problem 1) ->
 * verifyTwoFactorLogin(otp) -> full access + a real refresh token. Also guards Problem 2: the
 * empty pending-state refresh slot can never be exchanged for a token.
 */
final class VerifyTwoFactorLoginCest extends BaseCest
{
    private const VERIFY_LOGIN =
        'mutation ($otp: String!) { verifyTwoFactorLogin(otp: $otp) { accessToken refreshToken } }';

    public function loginForTwoFAUserReturnsChallengeWithEmptyRefreshSlot(AcceptanceTester $I): void
    {
        $this->prepareTwoFAChallengeUser($I);
        $user = $this->user();

        $login = $this->requestLoginChallenge($I);

        $claims = $this->claimsOf($I, $login['accessToken']);
        $I->assertTrue($claims->get('mfa_pending'), 'login() must return a pending challenge token');
        $I->assertTrue($claims->get('useranonymous'));
        $I->assertSame($user['userId'], $claims->get('userid'));

        // Problem 1: a pending user must NOT receive a usable refresh token.
        $I->assertSame('', $login['refreshToken'], 'Pending challenge must carry an empty refresh slot');
    }

    public function verifyTwoFactorLoginWithCorrectOtpReturnsAccessAndRefresh(AcceptanceTester $I): void
    {
        $this->prepareTwoFAChallengeUser($I);
        $user = $this->user();

        $challenge = $this->requestLoginChallenge($I)['accessToken'];
        $otp = $this->grabOtpFromEmail($I);

        $I->amBearerAuthenticated($challenge);
        $data = $this->sendGraphQL($I, self::VERIFY_LOGIN, ['otp' => $otp]);

        $result = $data['data']['verifyTwoFactorLogin'] ?? [];
        $I->assertNotEmpty($result['accessToken'] ?? null, 'Verified login must mint an access token');
        $I->assertNotEmpty($result['refreshToken'] ?? null, 'Verified login must now issue a real refresh token');

        $claims = $this->claimsOf($I, $result['accessToken']);
        $I->assertFalse($claims->get('mfa_pending', false));
        $I->assertFalse($claims->get('useranonymous'));
        $I->assertSame($user['userId'], $claims->get('userid'));

        $I->dontSeeInDatabase('oesm_2fa_otp', ['OXUSERID' => $user['userId']]);
    }

    public function pendingRefreshSlotCannotBeExchangedForAToken(AcceptanceTester $I): void
    {
        // Problem 2: the empty refresh slot handed to a pending user must be unusable.
        $this->prepareTwoFAChallengeUser($I);

        $pendingRefresh = $this->requestLoginChallenge($I)['refreshToken'];
        $I->assertSame('', $pendingRefresh);

        $I->logout();
        $data = $this->sendGraphQL(
            $I,
            'query ($rt: String!, $fp: String!) { refresh(refreshToken: $rt, fingerprintHash: $fp) }',
            ['rt' => $pendingRefresh, 'fp' => 'anything']
        );

        $I->assertArrayHasKey('errors', $data, 'An empty/pending refresh token must not mint anything');
        $I->assertNull($data['data']['refresh'] ?? null);
    }

    private function requestLoginChallenge(AcceptanceTester $I): array
    {
        $I->logout();
        $user = $this->user();
        $data = $this->sendGraphQL(
            $I,
            'query ($u: String, $p: String) { login(username: $u, password: $p) { accessToken refreshToken } }',
            ['u' => $user['userLoginName'], 'p' => $user['userPassword']]
        );

        return $data['data']['login'];
    }
}
