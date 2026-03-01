<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE `oxuser`
             ADD COLUMN `OESM2FAENABLED` TINYINT(1) NOT NULL DEFAULT 0
             COMMENT '2FA enabled for user'"
        );
    }
}
