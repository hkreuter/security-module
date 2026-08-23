<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Factory\UserModelFactoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountData;
use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\AccountNotFoundException;

class AccountRepository implements AccountRepositoryInterface
{
    private const SHOP_DEFAULT_LANGUAGE_SETTING = 'sDefaultLang';

    private const FALLBACK_LANGUAGE_ID = 0;

    public function __construct(
        private UserModelFactoryInterface $userModelFactory,
        private ShopConfigurationSettingDaoInterface $shopSettingDao,
    ) {
    }

    public function getById(string $userId): AccountDataInterface
    {
        $userModel = $this->userModelFactory->create();

        if (!$userModel->load($userId)) {
            throw new AccountNotFoundException();
        }

        $shopId = (int)$userModel->getFieldData('oxshopid');

        return new AccountData(
            userId: (string)$userModel->getId(),
            email: (string)$userModel->getFieldData('oxusername'),
            rights: (string)$userModel->getFieldData('oxrights'),
            languageId: $this->resolveShopDefaultLanguage($shopId),
            shopId: $shopId,
        );
    }

    private function resolveShopDefaultLanguage(int $shopId): int
    {
        try {
            return (int)$this->shopSettingDao
                ->get(self::SHOP_DEFAULT_LANGUAGE_SETTING, $shopId)
                ->getValue();
        } catch (EntryDoesNotExistDaoException) {
            return self::FALLBACK_LANGUAGE_ID;
        }
    }
}
