<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301120100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        if (!$schema->hasTable('oe_security_2fa')) {
            $this->addSql("
                CREATE TABLE `oe_security_2fa` (
                    `OXID` CHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
                    `OXUSERID` CHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
                    `OTP_CODE` VARCHAR(64) DEFAULT NULL,
                    `OTP_ATTEMPTS` INT NOT NULL DEFAULT 0,
                    `OTP_EXPIRES_AT` DATETIME DEFAULT NULL,
                    `SID` CHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
                    `CONTEXT` VARCHAR(8) NOT NULL DEFAULT 'frontend',
                    `LAST_SENT_AT` DATETIME DEFAULT NULL,
                    `CREATED_AT` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `UPDATED_AT` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`OXID`),
                    UNIQUE KEY `OXUSERID` (`OXUSERID`),
                    CONSTRAINT `FK_OE_SECURITY_2FA_OXUSER`
                        FOREIGN KEY (`OXUSERID`) REFERENCES `oxuser` (`OXID`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('oe_security_2fa')) {
            $this->addSql("DROP TABLE `oe_security_2fa`");
        }
    }
}
