<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = 'Deutsch';

$aLang = [
    'SHOP_MODULE_GROUP_password_policy'                     => 'Passwort-Einstellungen',
    'SHOP_MODULE_oeSecurityPasswordEnable'                  => 'Passwort-Richtlinien aktivieren',
    'SHOP_MODULE_oeSecurityPasswordMinimumLength'           => 'Minimale Länge',
    'HELP_SHOP_MODULE_oeSecurityPasswordMinimumLength'      => 'Mininmal erlaubte Passwortlänge. Sollte länger als 8 sein. ' .
        'Sollte die in den Moduleinstellungen gewählte Länge die der Shopeinstellung für minimale Passwortlänge unterschreiten, ' .
        'dann greift die Shopeinstellung.',
    'SHOP_MODULE_oeSecurityPasswordContainUppercase'        => 'Das Passwort muss mindestens einen Großbuchstaben enthalten',
    'SHOP_MODULE_oeSecurityPasswordContainLowercase'        => 'Das Passwort muss mindestens einen Kleinbuchstaben enthalten',
    'SHOP_MODULE_oeSecurityPasswordContainDigit'            => 'Das Passwort muss mindestens eine Zahl enthalten',
    'SHOP_MODULE_oeSecurityPasswordContainSpecialCharacter' => 'Das Passwort muss mindestens ein Sonderzeichen enthalten',

    'SHOP_MODULE_GROUP_captcha'                   => 'Captcha Einstellungen',
    'SHOP_MODULE_oeSecurityCaptchaEnable'         => 'Captcha Security aktivieren',
    'SHOP_MODULE_oeSecurityHoneyPotCaptchaEnable' => 'HoneyPot Captcha aktivieren',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime'       => 'Captcha Lebensdauer',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime_5min'  => '5 min',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime_15min' => '15 min',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime_30min' => '30 min',

    'SHOP_MODULE_GROUP_two_factor_auth'                 => 'Zwei-Faktor-Authentifizierung',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'        => 'Zwei-Faktor-Authentifizierung aktivieren',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'   => 'Aktiviert die Zwei-Faktor-Authentifizierung '
        . 'shop-weit. Wenn aktiv, müssen Benutzer mit aktivierter 2FA beim Login einen Code bestätigen.',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType'           => 'Art der Zwei-Faktor-Authentifizierung',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthType'      => 'Methode zur Übermittlung des zweiten '
        . 'Faktors. Derzeit wird nur "otp" (Einmal-Passwort per E-Mail) unterstützt. TOTP '
        . '(Authenticator-App) ist für ein zukünftiges Release geplant.',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_otp'       => 'OTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_totp'      => 'TOTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_both'      => 'OTP und TOTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthApiChallengeLifetime'      => 'Lebensdauer der API-2FA-Challenge (Sekunden)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthApiChallengeLifetime' => 'Wie lange ein API-2FA-Challenge-'
        . 'Bearer gegen ein vollständiges Access-Token eingelöst werden kann, in Sekunden. Die '
        . 'effektive Lebensdauer wird automatisch auf die OTP-Code-Lebensdauer (siehe unten) '
        . 'begrenzt — ein Bearer ist nie länger als der OTP-Code selbst nutzbar. Standard: 300 '
        . '(5 Minuten).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthOtpCodeLifetime'      => 'Lebensdauer des OTP-Codes (Sekunden)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthOtpCodeLifetime' => 'Wie lange ein per E-Mail versendeter '
        . 'OTP-Code gültig bleibt, in Sekunden. Gilt sowohl für den Storefront/Admin- als auch für '
        . 'den API-2FA-Flow. Bildet die Obergrenze für die API-Challenge-Lebensdauer oben. '
        . 'Standard: 300 (5 Minuten).',
];
