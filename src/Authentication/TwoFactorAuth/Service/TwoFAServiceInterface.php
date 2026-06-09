<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\CodeValidationException;

interface TwoFAServiceInterface
{
    public function isVerified(string $userId): bool;

    public function triggerChallenge(string $userId): void;

    public function invalidateChallenge(string $userId): void;

    /**
     * Consume a successfully-completed challenge so it cannot be reused (e.g. after the oxapi
     * verify mutation has minted its token). Counterpart to invalidateChallenge (abandon).
     */
    public function consumeChallenge(string $userId): void;

    /**
     * @throws CodeValidationException
     */
    public function verify(string $userId, #[\SensitiveParameter] string $code): void;
}
