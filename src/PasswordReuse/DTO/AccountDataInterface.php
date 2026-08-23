<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\DTO;

interface AccountDataInterface
{
    public function getUserId(): string;

    public function getEmail(): string;

    public function getRights(): string;

    public function getLanguageId(): int;

    public function getShopId(): int;
}
