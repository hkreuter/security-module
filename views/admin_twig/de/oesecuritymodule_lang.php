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

    'SHOP_MODULE_GROUP_two_factor_auth'                       => 'Zwei-Faktor-Authentifizierung (2FA)',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnable'               => 'Zwei-Faktor-Authentifizierung aktivieren',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthEnable'          => 'Wenn aktiviert, erhalten Benutzer bei der Anmeldung eine E-Mail mit einem Einmalcode.',
    'SHOP_MODULE_oeSecurityTwoFactorAuthOtpLength'            => 'OTP-Code-Länge',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthOtpLength'       => 'Anzahl der Ziffern des Einmalcodes (Standard: 6).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthOtpLifetime'          => 'OTP-Gültigkeitsdauer (Sekunden)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthOtpLifetime'     => 'Wie lange der Einmalcode in Sekunden gültig bleibt (Standard: 300).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthMaxAttempts'           => 'Max. Verifizierungsversuche',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthMaxAttempts'      => 'Maximale Anzahl fehlgeschlagener Versuche, bevor der Code ungültig wird (Standard: 3).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthCooldown'             => 'Wartezeit für erneutes Senden (Sekunden)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthCooldown'        => 'Mindestzeit in Sekunden, bevor ein neuer Code gesendet werden kann (Standard: 60).',
];
