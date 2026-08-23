<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\SecurityModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS `oesm_password_history` (
                `OXID`          char(32)     NOT NULL                        COMMENT "Surrogate primary key",
                `OXUSERID`      char(32)     NOT NULL                        COMMENT "Affected account identity",
                `PASSWORD_HASH` varchar(255) NOT NULL                        COMMENT "Salted hash of a superseded password",
                `SUPERSEDED_AT` datetime     NOT NULL                        COMMENT "Moment the hash was replaced",
                PRIMARY KEY (`OXID`),
                INDEX `OESM_PWDHIST_USER_SUPERSEDED` (`OXUSERID`, `SUPERSEDED_AT`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function down(Schema $schema): void
    {
    }
}
