<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\AcceptanceOXAPIStorefront;

use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * Password security over the graphql-storefront customerPasswordReset mutation.
 *
 * @group oe_security_module
 * @group oe_security_module_oxapi_password
 */
final class PasswordResetOxapiCest extends BaseCest
{
    private const FORGOT_MUTATION =
        'mutation ($email: String!) { customerPasswordForgotRequest(email: $email) }';

    private const RESET_MUTATION =
        'mutation ($h: String!, $p: String!, $r: String!) '
        . '{ customerPasswordReset(updateHash: $h, newPassword: $p, repeatPassword: $r) }';

    public function _before(AcceptanceTester $I): void
    {
        $this->prepare($I);
        $this->setReusePrevention(true);
        $this->setChangeNotification(true);
        $this->setPasswordPolicy(true);
    }

    public function _after(AcceptanceTester $I): void
    {
        $I->updateInDatabase('oxuser', ['oxupdatekey' => '', 'oxupdateexp' => 0], ['oxid' => $this->userId()]);
        $this->cleanup($I);
    }

    public function freshResetSucceedsAndNotifies(AcceptanceTester $I): void
    {
        $token = $this->requestResetToken($I);

        $data = $this->sendGraphQL($I, self::RESET_MUTATION, [
            'h' => $token,
            'p' => 'Fresh-reset-9!X',
            'r' => 'Fresh-reset-9!X',
        ]);

        $I->assertArrayNotHasKey('errors', $data, 'A valid reset must not raise a GraphQL error');
        $I->assertTrue($data['data']['customerPasswordReset'] ?? false, 'A valid reset returns true');
        $I->assertNotSame($this->baselineHash(), $this->storedHash($I), 'The stored password hash must change');

        $I->openRecentEmail();
        $I->assertNotEmpty($I->grabTextBodyFromEmail(), 'A genuine reset must notify the account holder');
    }

    public function tooWeakPasswordIsRejected(AcceptanceTester $I): void
    {
        $token = $this->requestResetToken($I);

        $data = $this->sendGraphQL($I, self::RESET_MUTATION, ['h' => $token, 'p' => 'ab', 'r' => 'ab']);

        $I->assertArrayHasKey('errors', $data, 'A policy-violating password must be rejected over OXAPI reset');
        $I->assertSame($this->baselineHash(), $this->storedHash($I), 'A rejected reset must not change the password');
    }

    public function reuseOfCurrentPasswordIsRejected(AcceptanceTester $I): void
    {
        $token = $this->requestResetToken($I);

        $data = $this->sendGraphQL($I, self::RESET_MUTATION, [
            'h' => $token,
            'p' => self::BASELINE_PASSWORD,
            'r' => self::BASELINE_PASSWORD,
        ]);

        $I->assertArrayHasKey('errors', $data, 'Resetting to the current password must be rejected as reuse');
        $I->assertSame($this->baselineHash(), $this->storedHash($I), 'A reuse-rejected reset must not change it');
    }

    public function reuseOfPreviousPasswordIsRejected(AcceptanceTester $I): void
    {
        $previousPlain = 'Previous-reset-7!';
        $this->appendHistory($this->hash($previousPlain));

        $token = $this->requestResetToken($I);

        $data = $this->sendGraphQL($I, self::RESET_MUTATION, [
            'h' => $token,
            'p' => $previousPlain,
            'r' => $previousPlain,
        ]);

        $I->assertArrayHasKey('errors', $data, 'Resetting to a remembered previous password must be rejected');
        $I->assertSame($this->baselineHash(), $this->storedHash($I), 'A reuse-rejected reset must not change it');
    }

    private function requestResetToken(AcceptanceTester $I): string
    {
        $this->sendGraphQL($I, self::FORGOT_MUTATION, ['email' => $this->user()['userLoginName']]);

        $I->openRecentEmail();
        $body = html_entity_decode($I->grabHtmlBodyFromEmail());
        $I->deleteAllEmails();

        if (!preg_match('/uid=([a-f0-9]{32})/', $body, $matches)) {
            throw new \RuntimeException('The forgot-password email did not contain a reset token.');
        }

        return $matches[1];
    }
}
