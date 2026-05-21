<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\FormSecurity\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;

class ModuleSettingsService implements ModuleSettingsServiceInterface
{
    public const GET_FORM_STRIP_STOKEN = 'oeSecurityGetFormStripStoken';

    public function __construct(
        private readonly ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function isGetFormStripStokenEnabled(): bool
    {
        return $this->moduleSettingService->getBoolean(self::GET_FORM_STRIP_STOKEN, Module::MODULE_ID);
    }
}
