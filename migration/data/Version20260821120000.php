<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\SecurityModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seeds the admin-editable CMS content for the password-change notification email.
 */
final class Version20260821120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $titleDe = 'Ihr OXID eShop Passwort wurde geändert';
        $bodyDe = <<<'TEXT'
            Hallo,

            das Passwort Ihres OXID eShop-Kontos wurde am {{ changedAt }} geändert.

            Falls Sie diese Änderung vorgenommen haben, müssen Sie nichts weiter tun.

            Falls Sie das nicht waren, kontaktieren Sie bitte umgehend unseren Support und setzen Sie Ihr Passwort zurück.

            Ihr OXID eShop-Team.
            TEXT;

        $titleEn = 'Your OXID eShop password was changed';
        $bodyEn = <<<'TEXT'
            Hello,

            the password for your OXID eShop account was changed on {{ changedAt }}.

            If you made this change, there is nothing more you need to do.

            If this wasn't you, please contact our support immediately and reset your password.

            Your OXID eShop team.
            TEXT;

        $this->addSql("DELETE FROM `oxcontents` WHERE `OXLOADID` = 'oesmpasswordchangeemail'");
        $this->addSql(
            "INSERT INTO `oxcontents`
                (`OXID`, `OXLOADID`, `OXSHOPID`, `OXSNIPPET`, `OXTYPE`, `OXACTIVE`, `OXACTIVE_1`, `OXFOLDER`,
                 `OXTITLE`, `OXCONTENT`, `OXTITLE_1`, `OXCONTENT_1`, `OXCONTENT_2`, `OXCONTENT_3`)
             VALUES
                (MD5('oesmpasswordchangeemail'), 'oesmpasswordchangeemail', 1, 1, 0, 1, 1, 'CMSFOLDER_EMAILS',
                 ?, ?, ?, ?, '', '')",
            [$titleDe, $bodyDe, $titleEn, $bodyEn]
        );
    }

    public function down(Schema $schema): void
    {
    }
}
