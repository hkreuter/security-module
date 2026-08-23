<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use DateTimeInterface;

interface PasswordHistoryServiceInterface
{
    public function record(
        string $userId,
        #[\SensitiveParameter] string $supersededHash,
        ?string $rights,
        DateTimeInterface $supersededAt
    ): void;

    public function purgeForUser(string $userId): void;
}
