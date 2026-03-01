<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        if ($schema->hasTable('oxuser')) {
            $table = $schema->getTable('oxuser');
            if (!$table->hasColumn('OESM2FAENABLED')) {
                $this->addSql(
                    "ALTER TABLE `oxuser` ADD COLUMN `OESM2FAENABLED` TINYINT(1) NOT NULL DEFAULT 0"
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('oxuser')) {
            $this->addSql("ALTER TABLE `oxuser` DROP COLUMN IF EXISTS `OESM2FAENABLED`");
        }
    }
}
