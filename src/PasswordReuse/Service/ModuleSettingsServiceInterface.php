<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

interface ModuleSettingsServiceInterface
{
    public function isReusePreventionEnabled(): bool;

    public function isChangeNotificationEnabled(): bool;

    public function getCustomerCollectionSize(): int;

    public function getAdminCollectionSize(): int;

    public function saveReusePreventionEnabled(bool $value): void;

    public function saveChangeNotificationEnabled(bool $value): void;

    public function resolveCollectionSizeForRights(?string $rights): int;
}
