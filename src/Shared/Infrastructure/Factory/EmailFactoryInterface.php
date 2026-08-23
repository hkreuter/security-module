<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\SecurityModule\Shared\Infrastructure\Factory;

use OxidEsales\Eshop\Core\Email;

interface EmailFactoryInterface
{
    public function create(): Email;
}
