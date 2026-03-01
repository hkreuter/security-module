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

    'SHOP_MODULE_GROUP_two_factor_auth'              => 'Zwei-Faktor-Authentifizierung',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'     => 'Zwei-Faktor-Authentifizierung aktivieren',
    'SHOP_MODULE_oeSecurityOtpLength'                => 'OTP Code-Länge',
    'SHOP_MODULE_oeSecurityOtpTtl'                   => 'OTP Gültigkeit (Sekunden)',
    'SHOP_MODULE_oeSecurityOtpMaxAttempts'            => 'Maximale Versuche',
    'SHOP_MODULE_oeSecurityOtpBlockDuration'          => 'Sperrdauer (Sekunden)',
    'SHOP_MODULE_oeSecurityOtpResendCooldown'         => 'Wartezeit vor erneutem Senden (Sekunden)',
];
