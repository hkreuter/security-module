<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Service;

use OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Exception\PasswordChangeRejected;
use OxidEsales\SecurityModule\PasswordPolicy\Service\ModuleSettingsServiceInterface as PasswordPolicySettings;
use OxidEsales\SecurityModule\PasswordPolicy\Validation\Exception\PasswordValidateException;
use OxidEsales\SecurityModule\PasswordPolicy\Validation\Service\PasswordValidatorChainInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;

final class PasswordChangeGuard implements PasswordChangeGuardInterface
{
    public function __construct(
        private readonly PasswordValidatorChainInterface $passwordValidator,
        private readonly PasswordPolicySettings $policySettings,
        private readonly PasswordReuseGuardServiceInterface $reuseGuard,
        private readonly StoredPasswordReaderInterface $storedPasswordReader,
        private readonly ConfirmedChangeRegistryInterface $confirmedChangeRegistry,
    ) {
    }

    public function guard(string $userId, #[\SensitiveParameter] string $candidate): void
    {
        if ($this->policySettings->isPasswordPolicyEnabled()) {
            try {
                $this->passwordValidator->validatePassword($candidate);
            } catch (PasswordValidateException $exception) {
                throw PasswordChangeRejected::policyViolation($exception);
            }
        }

        $currentHash = (string)$this->storedPasswordReader->getStoredPasswordHash($userId);

        try {
            $this->reuseGuard->guardChange($userId, $candidate, $currentHash);
        } catch (PasswordReuseException $exception) {
            throw PasswordChangeRejected::reused($exception);
        } catch (PasswordReuseCheckException $exception) {
            throw PasswordChangeRejected::checkFailed($exception);
        }

        $this->confirmedChangeRegistry->confirm($userId);
    }
}
