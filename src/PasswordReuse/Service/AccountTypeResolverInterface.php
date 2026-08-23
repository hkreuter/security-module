<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;

interface AccountTypeResolverInterface
{
    public function isAdmin(string $userId): bool;

    public function resolveAccount(string $userId): AccountDataInterface;
}
