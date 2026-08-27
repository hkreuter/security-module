<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\AcceptanceOXAPIStorefront;

use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * Password security over the graphql-storefront customerPasswordChange mutation.
 *
 * @group oe_security_module
 * @group oe_security_module_oxapi_password
 */
final class PasswordChangeOxapiCest extends BaseCest
{
    private const CHANGE_MUTATION =
        'mutation ($old: String!, $new: String!) { customerPasswordChange(old: $old, new: $new) { accessToken } }';

    public function _before(AcceptanceTester $I): void
    {
        $this->prepare($I);
        $this->setReusePrevention(true);
        $this->setChangeNotification(true);
        $this->setPasswordPolicy(true);
    }

    public function _after(AcceptanceTester $I): void
    {
        $this->cleanup($I);
    }

    public function freshPasswordChangeSucceedsAndNotifies(AcceptanceTester $I): void
    {
        $this->loginAsUser($I, self::BASELINE_PASSWORD);

        $data = $this->sendGraphQL($I, self::CHANGE_MUTATION, [
            'old' => self::BASELINE_PASSWORD,
            'new' => 'Fresh-strong-9!X',
        ]);

        $I->assertArrayNotHasKey('errors', $data, 'A valid password change must not raise a GraphQL error');
        $I->assertNotEmpty(
            $data['data']['customerPasswordChange']['accessToken'] ?? null,
            'A successful change re-logs the customer in and returns an access token'
        );
        $I->assertNotSame(
            $this->baselineHash(),
            $this->storedHash($I),
            'The stored password hash must actually change'
        );

        $I->openRecentEmail();
        $I->assertNotEmpty($I->grabTextBodyFromEmail(), 'A change-notification email must be delivered');
    }

    public function wrongCurrentPasswordIsRejected(AcceptanceTester $I): void
    {
        $this->loginAsUser($I, self::BASELINE_PASSWORD);

        $data = $this->sendGraphQL($I, self::CHANGE_MUTATION, [
            'old' => 'this-is-not-the-current-password',
            'new' => 'Another-strong-9!X',
        ]);

        $I->assertArrayHasKey('errors', $data, 'A wrong current password must be rejected');
        $I->assertSame(
            $this->baselineHash(),
            $this->storedHash($I),
            'A rejected change must leave the stored password untouched'
        );
    }

    public function reuseOfCurrentPasswordIsRejected(AcceptanceTester $I): void
    {
        $this->loginAsUser($I, self::BASELINE_PASSWORD);

        $data = $this->sendGraphQL($I, self::CHANGE_MUTATION, [
            'old' => self::BASELINE_PASSWORD,
            'new' => self::BASELINE_PASSWORD,
        ]);

        $I->assertArrayHasKey('errors', $data, 'Re-setting the current password over OXAPI must be rejected as reuse');
    }

    public function reuseOfPreviousPasswordIsRejected(AcceptanceTester $I): void
    {
        $previousPlain = 'Previous-pw-7!';
        $this->appendHistory($this->hash($previousPlain));

        $this->loginAsUser($I, self::BASELINE_PASSWORD);

        $data = $this->sendGraphQL($I, self::CHANGE_MUTATION, [
            'old' => self::BASELINE_PASSWORD,
            'new' => $previousPlain,
        ]);

        $I->assertArrayHasKey('errors', $data, 'Reusing a remembered previous password over OXAPI must be rejected');
        $I->assertSame(
            $this->baselineHash(),
            $this->storedHash($I),
            'A reuse-rejected change must leave the stored password untouched'
        );
    }

    public function tooWeakPasswordIsRejected(AcceptanceTester $I): void
    {
        $this->loginAsUser($I, self::BASELINE_PASSWORD);

        $data = $this->sendGraphQL($I, self::CHANGE_MUTATION, [
            'old' => self::BASELINE_PASSWORD,
            'new' => 'ab',
        ]);

        $I->assertArrayHasKey('errors', $data, 'A policy-violating (too short) password must be rejected over OXAPI');
        $I->assertSame(
            $this->baselineHash(),
            $this->storedHash($I),
            'A policy-rejected change must leave the stored password untouched'
        );
    }
}
