<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Storefront\Customer\Service;

use OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Exception\PasswordChangeRejected;
use OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Service\PasswordChangeGuard;
use OxidEsales\SecurityModule\PasswordPolicy\Service\ModuleSettingsServiceInterface as PasswordPolicySettings;
use OxidEsales\SecurityModule\PasswordPolicy\Validation\Exception\PasswordValidateException;
use OxidEsales\SecurityModule\PasswordPolicy\Validation\Service\PasswordValidatorChainInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PasswordChangeGuardTest extends TestCase
{
    private const USER_ID = 'user-42';
    private const CURRENT_HASH = '$2y$10$currenthashcurrenthashcurrenthashcurrenthashabc';
    private const CANDIDATE = 'Fresh-pw-9!X';

    #[Test]
    public function runsPolicyAndReuseThenConfirmsAGenuineChange(): void
    {
        $validator = $this->createMock(PasswordValidatorChainInterface::class);
        $validator->expects($this->once())->method('validatePassword')->with(self::CANDIDATE);

        $reuseGuard = $this->createMock(PasswordReuseGuardServiceInterface::class);
        $reuseGuard->expects($this->once())->method('guardChange')->with(self::USER_ID, self::CANDIDATE, self::CURRENT_HASH);

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->once())->method('confirm')->with(self::USER_ID);

        $this->getSut(validator: $validator, reuseGuard: $reuseGuard, registry: $registry)
            ->guard(self::USER_ID, self::CANDIDATE);
    }

    #[Test]
    public function rejectsAReusedPasswordAndNeverConfirms(): void
    {
        $reuseGuard = $this->createStub(PasswordReuseGuardServiceInterface::class);
        $reuseGuard->method('guardChange')->willThrowException(new PasswordReuseException());

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $this->expectException(PasswordChangeRejected::class);

        $this->getSut(reuseGuard: $reuseGuard, registry: $registry)->guard(self::USER_ID, self::CANDIDATE);
    }

    #[Test]
    public function rejectsAPolicyViolationBeforeTheReuseGuardAndNeverConfirms(): void
    {
        $validator = $this->createStub(PasswordValidatorChainInterface::class);
        $validator->method('validatePassword')->willThrowException(new PasswordValidateException('too weak'));

        $reuseGuard = $this->createMock(PasswordReuseGuardServiceInterface::class);
        $reuseGuard->expects($this->never())->method('guardChange');

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $this->expectException(PasswordChangeRejected::class);

        $this->getSut(validator: $validator, reuseGuard: $reuseGuard, registry: $registry)
            ->guard(self::USER_ID, self::CANDIDATE);
    }

    #[Test]
    public function surfacesAFailClosedReuseCheckError(): void
    {
        $reuseGuard = $this->createStub(PasswordReuseGuardServiceInterface::class);
        $reuseGuard->method('guardChange')->willThrowException(new PasswordReuseCheckException());

        $this->expectException(PasswordChangeRejected::class);

        $this->getSut(reuseGuard: $reuseGuard)->guard(self::USER_ID, self::CANDIDATE);
    }

    #[Test]
    public function skipsPolicyValidationWhenThePolicyIsDisabledButStillGuardsReuse(): void
    {
        $settings = $this->createStub(PasswordPolicySettings::class);
        $settings->method('isPasswordPolicyEnabled')->willReturn(false);

        $validator = $this->createMock(PasswordValidatorChainInterface::class);
        $validator->expects($this->never())->method('validatePassword');

        $reuseGuard = $this->createMock(PasswordReuseGuardServiceInterface::class);
        $reuseGuard->expects($this->once())->method('guardChange');

        $this->getSut(validator: $validator, reuseGuard: $reuseGuard, settings: $settings)
            ->guard(self::USER_ID, self::CANDIDATE);
    }

    private function getSut(
        ?PasswordValidatorChainInterface $validator = null,
        ?PasswordPolicySettings $settings = null,
        ?PasswordReuseGuardServiceInterface $reuseGuard = null,
        ?StoredPasswordReaderInterface $reader = null,
        ?ConfirmedChangeRegistryInterface $registry = null,
    ): PasswordChangeGuard {
        if ($settings === null) {
            $settings = $this->createStub(PasswordPolicySettings::class);
            $settings->method('isPasswordPolicyEnabled')->willReturn(true);
        }

        if ($reader === null) {
            $reader = $this->createStub(StoredPasswordReaderInterface::class);
            $reader->method('getStoredPasswordHash')->willReturn(self::CURRENT_HASH);
        }

        return new PasswordChangeGuard(
            $validator ?? $this->createStub(PasswordValidatorChainInterface::class),
            $settings,
            $reuseGuard ?? $this->createStub(PasswordReuseGuardServiceInterface::class),
            $reader,
            $registry ?? $this->createStub(ConfirmedChangeRegistryInterface::class),
        );
    }
}
