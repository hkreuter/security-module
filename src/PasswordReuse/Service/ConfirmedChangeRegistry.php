<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

class ConfirmedChangeRegistry implements ConfirmedChangeRegistryInterface
{
    /** @var array<string, true> */
    private array $confirmed = [];

    public function confirm(string $userId): void
    {
        $this->confirmed[$userId] = true;
    }

    public function isConfirmed(string $userId): bool
    {
        return isset($this->confirmed[$userId]);
    }

    public function clear(string $userId): void
    {
        unset($this->confirmed[$userId]);
    }
}
