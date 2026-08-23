<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository;

use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\AccountNotFoundException;

interface AccountRepositoryInterface
{
    /** @throws AccountNotFoundException */
    public function getById(string $userId): AccountDataInterface;
}
