<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301120100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE `oe_security_2fa` (
                `OXID`           CHAR(32) NOT NULL,
                `OXUSERID`       CHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
                `OTP_CODE`       VARCHAR(64) DEFAULT NULL COMMENT 'SHA-256 hash of OTP, salted with OXUSERID',
                `OTP_ATTEMPTS`   INT NOT NULL DEFAULT 0,
                `OTP_EXPIRES_AT` DATETIME DEFAULT NULL,
                `SID`            CHAR(32) DEFAULT NULL,
                `CONTEXT`        VARCHAR(8) NOT NULL DEFAULT 'frontend' COMMENT 'Login context: frontend or admin',
                `LAST_SENT_AT`   DATETIME DEFAULT NULL,
                `CREATED_AT`     DATETIME DEFAULT CURRENT_TIMESTAMP,
                `UPDATED_AT`     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Also serves as implicit BLOCKED_AT',
                PRIMARY KEY (`OXID`),
                UNIQUE KEY `idx_oxuserid` (`OXUSERID`),
                CONSTRAINT `fk_oe_security_2fa_oxuser` FOREIGN KEY (`OXUSERID`) REFERENCES `oxuser` (`OXID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
