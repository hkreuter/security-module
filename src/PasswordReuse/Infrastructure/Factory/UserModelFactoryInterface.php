<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Factory;

use OxidEsales\Eshop\Application\Model\User;

interface UserModelFactoryInterface
{
    public function create(): User;
}
