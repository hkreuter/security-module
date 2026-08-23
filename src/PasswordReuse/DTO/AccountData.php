<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\DTO;

class AccountData implements AccountDataInterface
{
    public function __construct(
        private string $userId,
        private string $email,
        private string $rights,
        private int $languageId,
        private int $shopId,
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRights(): string
    {
        return $this->rights;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }
}
