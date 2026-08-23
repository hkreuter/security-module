<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;

interface PasswordCollectionServiceInterface
{
    /**
     * @throws PasswordReuseCheckException when the collection cannot be built or a member cannot be verified
     */
    public function isCandidateInCollection(
        string $userId,
        #[\SensitiveParameter] string $candidate,
        string $currentHash,
    ): bool;
}
