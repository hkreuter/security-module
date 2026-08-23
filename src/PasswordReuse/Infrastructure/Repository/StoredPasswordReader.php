<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class StoredPasswordReader implements StoredPasswordReaderInterface
{
    private const TABLE = 'oxuser';

    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
    ) {
    }

    public function getStoredPasswordHash(string $userId): ?string
    {
        $builder = $this->queryBuilderFactory->create();
        $builder->select('OXPASSWORD')
            ->from(self::TABLE)
            ->where('OXID = :userId')
            ->setParameter('userId', $userId);

        /** @var \Doctrine\DBAL\Result $result */
        $result = $builder->execute();
        $hash = $result->fetchOne();

        return $hash === false ? null : (string)$hash;
    }
}
