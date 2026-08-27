<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Support\Helper;

use OxidEsales\Facts\Facts;

final class AcceptanceOxapiStorefront extends \Codeception\Module
{
    public function _beforeSuite($settings = []): void
    {
        $console = (new Facts())->getShopRootPath() . '/vendor/bin/oe-console';
        exec($console . ' oe:module:activate oe_graphql_base');
        exec($console . ' oe:module:activate oe_graphql_storefront');
        exec($console . ' oe:module:activate oe_security_module');
    }
}
