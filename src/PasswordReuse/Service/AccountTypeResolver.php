<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\AccountRepositoryInterface;

class AccountTypeResolver implements AccountTypeResolverInterface
{
    private const CUSTOMER_RIGHTS = 'user';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function isAdmin(string $userId): bool
    {
        $rights = $this->accountRepository->getById($userId)->getRights();

        return $rights !== '' && $rights !== self::CUSTOMER_RIGHTS;
    }

    public function resolveAccount(string $userId): AccountDataInterface
    {
        return $this->accountRepository->getById($userId);
    }
}
