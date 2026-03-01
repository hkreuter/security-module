<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301120200 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO oxcontents (
                OXID, OXLOADID, OXSHOPID, OXSNIPPET, OXTYPE,
                OXACTIVE, OXACTIVE_1,
                OXTITLE, OXCONTENT,
                OXTITLE_1, OXCONTENT_1,
                OXFOLDER
            ) VALUES (
                'oesm2faotpemail',
                'oesm2faotpemail',
                1, 1, 0,
                1, 1,
                'Bestätigungscode für die Anmeldung',
                '<p>Sie haben einen Bestätigungscode für die Anmeldung bei Ihrem Konto angefordert.</p>\r\n<p>Ihr Bestätigungscode lautet:</p>\r\n<h2 style=\"text-align: center; letter-spacing: 6px; padding: 15px 0;\">{{ otpCode }}</h2>\r\n<p>Dieser Code ist {{ lifetimeMinutes }} Minuten gültig.</p>\r\n<p><small>Falls Sie diesen Code nicht angefordert haben, können Sie diese E-Mail ignorieren. Geben Sie diesen Code nicht an Dritte weiter.</small></p>',
                'Verification code for sign-in',
                '<p>You requested a verification code to sign in to your account.</p>\r\n<p>Your verification code is:</p>\r\n<h2 style=\"text-align: center; letter-spacing: 6px; padding: 15px 0;\">{{ otpCode }}</h2>\r\n<p>This code is valid for {{ lifetimeMinutes }} minutes.</p>\r\n<p><small>If you did not request this code, you can safely ignore this email. Do not share this code with anyone.</small></p>',
                'CMSFOLDER_EMAILS'
            )
        ");

        $this->addSql("
            INSERT INTO oxcontents (
                OXID, OXLOADID, OXSHOPID, OXSNIPPET, OXTYPE,
                OXACTIVE, OXACTIVE_1,
                OXTITLE, OXCONTENT,
                OXTITLE_1, OXCONTENT_1,
                OXFOLDER
            ) VALUES (
                'oesm2faotpplainemail',
                'oesm2faotpplainemail',
                1, 1, 0,
                1, 1,
                'Bestätigungscode für die Anmeldung (Text)',
                'Sie haben einen Bestätigungscode für die Anmeldung bei Ihrem Konto angefordert.\r\n\r\nIhr Bestätigungscode lautet: {{ otpCode }}\r\n\r\nDieser Code ist {{ lifetimeMinutes }} Minuten gültig.\r\n\r\nFalls Sie diesen Code nicht angefordert haben, können Sie diese E-Mail ignorieren. Geben Sie diesen Code nicht an Dritte weiter.',
                'Verification code for sign-in (plain)',
                'You requested a verification code to sign in to your account.\r\n\r\nYour verification code is: {{ otpCode }}\r\n\r\nThis code is valid for {{ lifetimeMinutes }} minutes.\r\n\r\nIf you did not request this code, you can safely ignore this email. Do not share this code with anyone.',
                'CMSFOLDER_EMAILS'
            )
        ");
    }
}
