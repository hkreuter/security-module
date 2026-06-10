<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

use OxidEsales\Codeception\Module\Database\DatabaseDefaultsFileGenerator;
use OxidEsales\Facts\Config\ConfigFile;
use OxidEsales\Facts\Facts;
use Symfony\Component\Filesystem\Path;

if ($shopRootPath = getenv('SHOP_ROOT_PATH')){
    require_once(Path::join($shopRootPath, 'source', 'bootstrap.php'));
}

$facts = new Facts();

return [
    'SHOP_URL' => $facts->getShopUrl(),
    'SHOP_SOURCE_PATH' => $facts->getSourcePath(),
    'SOURCE_RELATIVE_PACKAGE_PATH' => getSourceRelativePackagePath($facts),
    'VENDOR_PATH' => $facts->getVendorPath(),
    'DB_NAME' => $facts->getDatabaseName(),
    'DB_USERNAME' => $facts->getDatabaseUserName(),
    'DB_PASSWORD' => $facts->getDatabasePassword(),
    'DB_HOST' => $facts->getDatabaseHost(),
    'DB_PORT' => $facts->getDatabasePort(),
    'DUMP_PATH' => getTemporaryDataDumpFilePath(),
    'MODULE_DUMP_PATH' => getCodeceptionSpecificFixtureFilePath(),
    'MYSQL_CONFIG_PATH' => getMysqlConfigPath(),
    'FIXTURES_PATH' => getGenericFixtureSqlFilePath(),
    'SELENIUM_SERVER_PORT' => getenv('SELENIUM_SERVER_PORT') ?: '4444',
    'SELENIUM_SERVER_HOST' => getenv('SELENIUM_SERVER_HOST') ?: 'selenium',
    'BROWSER_NAME' => getenv('BROWSER_NAME') ?: 'chrome',
    'PHP_BIN' => getenv('PHPBIN') ?: 'php',
    'SCREEN_SHOT_URL' => getenv('CC_SCREEN_SHOTS_PATH') ?: '',
    'THEME_ID' => getenv('THEME_ID') ?: 'apex',
    'MAIL_HOST' => getenv('MAIL_HOST') ?: 'mailpit',
    'MAIL_WEB_PORT' => getenv('MAIL_WEB_PORT') ?: '8025',
];

function getTemporaryDataDumpFilePath(): string
{
    return Path::join(__DIR__, '../Support/Data', 'generated', 'dump.sql');
}

function getSourceRelativePackagePath(Facts $facts): string
{
    return str_replace($facts->getShopRootPath(), '..', __DIR__) . '/../../../';
}

function getCodeceptionSpecificFixtureFilePath(): string
{
    $path = 'fixtures.sql';

    $facts = new Facts();
    if ($facts->getEdition() == 'EE') {
        $path = 'fixtures_ee.sql';
    }

    return Path::join(__DIR__, '../Support/Data', $path);
}

function getMysqlConfigPath(): string
{
    $facts = new Facts();
    $configFilePath = Path::join($facts->getSourcePath(), 'config.inc.php');
    $configFile = new ConfigFile($configFilePath);
    $generator = new DatabaseDefaultsFileGenerator($configFile);

    return $generator->generate();
}

function getGenericFixtureSqlFilePath(): string
{
    $facts = new Facts();
    return Path::join(__DIR__, '/../../', 'Fixtures', 'testdata_' . strtolower($facts->getEdition()) . '.sql');
}
