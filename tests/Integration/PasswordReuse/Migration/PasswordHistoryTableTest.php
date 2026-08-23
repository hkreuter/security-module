<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Migration;

use Doctrine\DBAL\Connection;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordHistoryTableTest extends IntegrationTestCase
{
    private const TABLE = 'oesm_password_history';

    #[Test]
    public function passwordHistoryTableExistsAfterMigration(): void
    {
        $schemaManager = $this->getConnection()->getSchemaManager();

        $this->assertTrue(
            $schemaManager->tablesExist([self::TABLE]),
            sprintf('Expected table "%s" to exist after migration.', self::TABLE)
        );
    }

    #[Test]
    public function passwordHistoryTableHasExpectedColumns(): void
    {
        $columnNames = array_map(
            static fn(string $name): string => strtoupper($name),
            array_keys(
                $this->getConnection()->getSchemaManager()->listTableColumns(self::TABLE)
            )
        );

        foreach (['OXID', 'OXUSERID', 'PASSWORD_HASH', 'SUPERSEDED_AT'] as $expected) {
            $this->assertContains(
                $expected,
                $columnNames,
                sprintf('Expected column "%s" on table "%s".', $expected, self::TABLE)
            );
        }
    }

    #[Test]
    public function passwordHistoryTableIsKeyedByOxidPrimaryKey(): void
    {
        $primaryKey = $this->getConnection()
            ->getSchemaManager()
            ->listTableDetails(self::TABLE)
            ->getPrimaryKey();

        $this->assertNotNull($primaryKey, 'Expected a primary key on ' . self::TABLE);
        $this->assertSame(
            ['OXID'],
            array_map('strtoupper', $primaryKey->getColumns())
        );
    }

    #[Test]
    public function passwordHistoryTableHasCompositeUserSupersededIndex(): void
    {
        $indexes = $this->getConnection()
            ->getSchemaManager()
            ->listTableIndexes(self::TABLE);

        $hasCompositeIndex = false;
        foreach ($indexes as $index) {
            if (
                array_map('strtoupper', $index->getColumns())
                === ['OXUSERID', 'SUPERSEDED_AT']
            ) {
                $hasCompositeIndex = true;
                break;
            }
        }

        $this->assertTrue(
            $hasCompositeIndex,
            sprintf(
                'Expected a composite (OXUSERID, SUPERSEDED_AT) index on table "%s".',
                self::TABLE
            )
        );
    }

    private function getConnection(): Connection
    {
        return $this->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection();
    }
}
