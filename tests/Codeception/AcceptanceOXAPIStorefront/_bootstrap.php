<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

// This is the OXAPI storefront acceptance bootstrap
use OxidEsales\Codeception\Module\FixturesHelper;
use Symfony\Component\Filesystem\Path;

require_once Path::join((new \OxidEsales\Facts\Facts())->getShopRootPath(), 'source', 'bootstrap.php');

$helper = new FixturesHelper();
$helper->loadRuntimeFixtures(__DIR__ . '/../Support/Data/users.php');
