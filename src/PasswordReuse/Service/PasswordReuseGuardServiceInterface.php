<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;

interface PasswordReuseGuardServiceInterface
{
    /**
     * @throws PasswordReuseException when the candidate reuses a member of the account's collection
     * @throws PasswordReuseCheckException when the reuse check cannot be completed (fail-closed)
     */
    public function guardChange(
        string $userId,
        #[\SensitiveParameter] string $candidate,
        string $currentHash,
    ): void;
}
