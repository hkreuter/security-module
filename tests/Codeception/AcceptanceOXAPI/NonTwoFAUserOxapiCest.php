<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\AcceptanceOXAPI;

use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * Regression guard: with shop-level 2FA on but the user NOT enrolled, the oxapi login path behaves
 * exactly as stock graphql-base — the SecureApiLegacy / BeforeTokenCreation / refresh-token
 * decorators must leave a normal login untouched.
 */
final class NonTwoFAUserOxapiCest extends BaseCest
{
    public function tokenForNonTwoFAUserReturnsAFullAccessToken(AcceptanceTester $I): void
    {
        $this->prepareRegularUser($I);
        $user = $this->user();

        $data = $this->sendGraphQL(
            $I,
            'query ($u: String, $p: String) { token(username: $u, password: $p) }',
            ['u' => $user['userLoginName'], 'p' => $user['userPassword']]
        );

        $token = $data['data']['token'] ?? null;
        $I->assertNotEmpty($token, 'A non-2FA user must receive a token directly');

        $claims = $this->claimsOf($I, $token);
        $I->assertFalse($claims->get('mfa_pending', false), 'Full token must not carry mfa_pending');
        $I->assertFalse($claims->get('useranonymous'), 'Full token must be a real (non-anonymous) user');
        $I->assertSame($user['userId'], $claims->get('userid'));
    }

    public function loginForNonTwoFAUserReturnsAccessAndRefreshTokens(AcceptanceTester $I): void
    {
        $this->prepareRegularUser($I);
        $user = $this->user();

        $data = $this->sendGraphQL(
            $I,
            'query ($u: String, $p: String) { login(username: $u, password: $p) { accessToken refreshToken } }',
            ['u' => $user['userLoginName'], 'p' => $user['userPassword']]
        );

        $login = $data['data']['login'] ?? [];
        $I->assertNotEmpty($login['accessToken'] ?? null, 'A non-2FA user must receive an access token');
        $I->assertNotEmpty($login['refreshToken'] ?? null, 'A non-2FA user must receive a usable refresh token');

        $claims = $this->claimsOf($I, $login['accessToken']);
        $I->assertFalse($claims->get('mfa_pending', false));
        $I->assertFalse($claims->get('useranonymous'));
    }
}
