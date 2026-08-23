<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository;

use DateTimeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;

class PasswordHistoryRepository implements PasswordHistoryRepositoryInterface
{
    private const TABLE = 'oesm_password_history';
    private const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
        private readonly ShopAdapterInterface $shopAdapter,
    ) {
    }

    public function append(string $userId, string $hash, DateTimeInterface $supersededAt): void
    {
        $builder = $this->queryBuilderFactory->create();
        $builder->insert(self::TABLE)
            ->values([
                'OXID'          => ':oxid',
                'OXUSERID'      => ':userId',
                'PASSWORD_HASH' => ':hash',
                'SUPERSEDED_AT' => ':supersededAt',
            ])
            ->setParameters([
                'oxid'         => $this->shopAdapter->generateUniqueId(),
                'userId'       => $userId,
                'hash'         => $hash,
                'supersededAt' => $supersededAt->format(self::DB_DATETIME_FORMAT),
            ])
            ->execute();
    }

    public function findRecentHashes(string $userId, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $builder = $this->queryBuilderFactory->create();
        $builder->select('PASSWORD_HASH')
            ->from(self::TABLE)
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('SUPERSEDED_AT', 'DESC')
            ->addOrderBy('OXID', 'DESC')
            ->setMaxResults($limit);

        /** @var \Doctrine\DBAL\Result $result */
        $result = $builder->execute();

        return array_map(
            static fn(array $row): string => (string)$row['PASSWORD_HASH'],
            $result->fetchAllAssociative()
        );
    }

    public function deleteSurplusBeyond(string $userId, int $keep): void
    {
        foreach ($this->findSurplusOldestIds($userId, $keep) as $oxid) {
            $this->deleteById($oxid);
        }
    }

    public function purgeForUser(string $userId): void
    {
        $builder = $this->queryBuilderFactory->create();
        $builder->delete(self::TABLE)
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
            ->execute();
    }

    public function purgeAll(): void
    {
        $builder = $this->queryBuilderFactory->create();
        $builder->delete(self::TABLE)->execute();
    }

    public function countForUser(string $userId): int
    {
        $builder = $this->queryBuilderFactory->create();
        $builder->select('COUNT(*)')
            ->from(self::TABLE)
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId);

        /** @var \Doctrine\DBAL\Result $result */
        $result = $builder->execute();

        return (int)$result->fetchOne();
    }

    /**
     * @return list<string>
     */
    private function findSurplusOldestIds(string $userId, int $keep): array
    {
        $builder = $this->queryBuilderFactory->create();
        $builder->select('OXID')
            ->from(self::TABLE)
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('SUPERSEDED_AT', 'DESC')
            ->addOrderBy('OXID', 'DESC');

        /** @var \Doctrine\DBAL\Result $result */
        $result = $builder->execute();

        $oxids = array_map(
            static fn(array $row): string => (string)$row['OXID'],
            $result->fetchAllAssociative()
        );

        return array_slice($oxids, max($keep, 0));
    }

    private function deleteById(string $oxid): void
    {
        $builder = $this->queryBuilderFactory->create();
        $builder->delete(self::TABLE)
            ->where('OXID = :oxid')
            ->setParameter('oxid', $oxid)
            ->execute();
    }
}
