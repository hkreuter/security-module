<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;

class ModuleSettingsService implements ModuleSettingsServiceInterface
{
    public const REUSE_PREVENTION_ENABLE = 'oeSecurityPasswordReuseEnable';

    public const CHANGE_NOTIFICATION_ENABLE = 'oeSecurityPasswordChangeNotificationEnable';

    public const CUSTOMER_COLLECTION_SIZE = 'oeSecurityPasswordReuseCustomerSize';

    public const ADMIN_COLLECTION_SIZE = 'oeSecurityPasswordReuseAdminSize';

    private const CUSTOMER_RIGHTS = 'user';

    public function __construct(
        private readonly ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function isReusePreventionEnabled(): bool
    {
        return $this->moduleSettingService->getBoolean(self::REUSE_PREVENTION_ENABLE, Module::MODULE_ID);
    }

    public function isChangeNotificationEnabled(): bool
    {
        return $this->moduleSettingService->getBoolean(self::CHANGE_NOTIFICATION_ENABLE, Module::MODULE_ID);
    }

    public function getCustomerCollectionSize(): int
    {
        return $this->readCollectionSize(self::CUSTOMER_COLLECTION_SIZE);
    }

    public function getAdminCollectionSize(): int
    {
        return $this->readCollectionSize(self::ADMIN_COLLECTION_SIZE);
    }

    public function saveReusePreventionEnabled(bool $value): void
    {
        $this->moduleSettingService->saveBoolean(self::REUSE_PREVENTION_ENABLE, $value, Module::MODULE_ID);
    }

    public function saveChangeNotificationEnabled(bool $value): void
    {
        $this->moduleSettingService->saveBoolean(self::CHANGE_NOTIFICATION_ENABLE, $value, Module::MODULE_ID);
    }

    public function resolveCollectionSizeForRights(?string $rights): int
    {
        $customerSize = $this->getCustomerCollectionSize();

        if (!$this->isAdminRights($rights)) {
            return $customerSize;
        }

        return max($this->getAdminCollectionSize(), $customerSize);
    }

    private function isAdminRights(?string $rights): bool
    {
        return $rights !== null && $rights !== '' && $rights !== self::CUSTOMER_RIGHTS;
    }

    private function readCollectionSize(string $settingName): int
    {
        return (int) $this->moduleSettingService
            ->getString($settingName, Module::MODULE_ID)
            ->toString();
    }
}
