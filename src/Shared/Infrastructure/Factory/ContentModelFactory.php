<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Infrastructure\Factory;

use OxidEsales\Eshop\Application\Model\Content;

class ContentModelFactory implements ContentModelFactoryInterface
{
    public function create(): Content
    {
        return oxNew(Content::class);
    }
}
