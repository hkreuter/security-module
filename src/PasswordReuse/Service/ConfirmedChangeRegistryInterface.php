<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

interface ConfirmedChangeRegistryInterface
{
    public function confirm(string $userId): void;

    public function isConfirmed(string $userId): bool;

    public function clear(string $userId): void;
}
