<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\AcceptanceOXAPI;

use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * The access-token-only flow: token() -> mfa_pending challenge token -> verifyTwoFactorToken(otp)
 * -> a usable, non-anonymous access token. OTP read from the real verification email in Mailpit.
 */
final class VerifyTwoFactorTokenCest extends BaseCest
{
    private const VERIFY_TOKEN = 'mutation ($otp: String!) { verifyTwoFactorToken(otp: $otp) }';
    private const LOGGED_PROBE = 'query { tokens { id } }';

    public function tokenForTwoFAUserReturnsAnonymousChallengeTokenAndSendsOtp(AcceptanceTester $I): void
    {
        $this->prepareTwoFAChallengeUser($I);
        $user = $this->user();

        $challenge = $this->requestChallengeToken($I);

        $claims = $this->claimsOf($I, $challenge);
        $I->assertTrue($claims->get('mfa_pending'), 'Challenge token must carry mfa_pending');
        $I->assertTrue($claims->get('useranonymous'), 'Challenge token must be anonymous');
        $I->assertSame($user['userId'], $claims->get('userid'), 'Challenge token keeps the real user id');

        // Short, self-issued challenge window (default 300s) — far below the JWT's own (8h) exp.
        $now = time();
        $mfaExp = (int)$claims->get('mfa_exp');
        $I->assertGreaterThan($now, $mfaExp, 'Challenge expiry must be in the future');
        $I->assertLessThanOrEqual($now + 300, $mfaExp, 'Default challenge lifetime is 5 minutes');
        $I->assertLessThan(
            $claims->get('exp')->getTimestamp(),
            $mfaExp,
            'Challenge expiry must be far shorter than the full token exp'
        );

        $I->seeInDatabase('oesm_2fa_otp', ['OXUSERID' => $user['userId']]);
        // The OTP email actually went out to this user, carrying a 6-digit code.
        $I->openRecentEmail();
        $I->seeInEmailTo($user['userLoginName']);
        $I->assertMatchesRegularExpression('/^\d{6}$/', $this->grabOtpFromEmail($I));
    }

    public function challengeLifetimeIsDrivenByTheModuleSetting(AcceptanceTester $I): void
    {
        $this->prepareTwoFAChallengeUser($I);
        $this->setApiChallengeLifetime(60); // override the default 5 minutes

        $now = time();
        $challenge = $this->requestChallengeToken($I);

        $mfaExp = (int)$this->claimsOf($I, $challenge)->get('mfa_exp');
        $I->assertGreaterThan($now, $mfaExp);
        $I->assertLessThanOrEqual($now + 60, $mfaExp, 'mfa_exp must honour the configured 60s lifetime');
    }

    public function challengeTokenIsRejectedOnALoggedQuery(AcceptanceTester $I): void
    {
        $this->prepareTwoFAChallengeUser($I);

        $challenge = $this->requestChallengeToken($I);
        $I->amBearerAuthenticated($challenge);

        $data = $this->sendGraphQL($I, self::LOGGED_PROBE);

        $I->assertArrayHasKey('errors', $data, 'An anonymous challenge token must not satisfy #[Logged]');
        $I->assertEmpty($data['data']['tokens'] ?? null);
    }

    public function verifyTwoFactorTokenWithCorrectOtpReturnsUsableAccessToken(AcceptanceTester $I): void
    {
        $this->prepareTwoFAChallengeUser($I);
        $user = $this->user();

        $challenge = $this->requestChallengeToken($I);
        $otp = $this->grabOtpFromEmail($I);

        $I->amBearerAuthenticated($challenge);
        $data = $this->sendGraphQL($I, self::VERIFY_TOKEN, ['otp' => $otp]);

        $accessToken = $data['data']['verifyTwoFactorToken'] ?? null;
        $I->assertNotEmpty($accessToken, 'A correct OTP must mint a full access token');

        $claims = $this->claimsOf($I, $accessToken);
        $I->assertFalse($claims->get('mfa_pending', false), 'Minted token must not be a pending challenge');
        $I->assertFalse($claims->get('useranonymous'), 'Minted token must be a real user');
        $I->assertSame($user['userId'], $claims->get('userid'));

        // The challenge is consumed exactly once, after minting.
        $I->dontSeeInDatabase('oesm_2fa_otp', ['OXUSERID' => $user['userId']]);

        // And the minted token actually works on a #[Logged] query.
        $I->amBearerAuthenticated($accessToken);
        $probe = $this->sendGraphQL($I, self::LOGGED_PROBE);
        $I->assertArrayNotHasKey('errors', $probe, 'Minted token must satisfy #[Logged]');
    }

    public function verifyTwoFactorTokenWithWrongOtpFailsAndKeepsTheChallenge(AcceptanceTester $I): void
    {
        $this->prepareTwoFAChallengeUser($I);
        $user = $this->user();

        $challenge = $this->requestChallengeToken($I);
        $this->grabOtpFromEmail($I); // ensure a code exists; we deliberately submit a wrong one

        $I->amBearerAuthenticated($challenge);
        $data = $this->sendGraphQL($I, self::VERIFY_TOKEN, ['otp' => '000000']);

        $I->assertArrayHasKey('errors', $data, 'A wrong OTP must error');
        $I->assertNull($data['data']['verifyTwoFactorToken'] ?? null, 'No token on a wrong OTP');
        $I->seeInDatabase('oesm_2fa_otp', ['OXUSERID' => $user['userId']]);
    }

    public function verifyTwoFactorTokenRejectsANonChallengeToken(AcceptanceTester $I): void
    {
        // A full (already-authenticated) token must not be accepted as a 2FA challenge.
        $this->prepareRegularUser($I);
        $fullToken = $this->requestChallengeToken($I); // regular user -> this is a normal full token

        $I->amBearerAuthenticated($fullToken);
        $data = $this->sendGraphQL($I, self::VERIFY_TOKEN, ['otp' => '000000']);

        $I->assertArrayHasKey('errors', $data);
        $I->assertNull($data['data']['verifyTwoFactorToken'] ?? null);
        $I->assertStringContainsString('two-factor', strtolower($data['errors'][0]['message'] ?? ''));
    }

    private function requestChallengeToken(AcceptanceTester $I): string
    {
        $I->logout();
        $user = $this->user();
        $data = $this->sendGraphQL(
            $I,
            'query ($u: String, $p: String) { token(username: $u, password: $p) }',
            ['u' => $user['userLoginName'], 'p' => $user['userPassword']]
        );

        return $data['data']['token'];
    }
}
