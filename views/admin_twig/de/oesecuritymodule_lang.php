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

    'SHOP_MODULE_GROUP_two_factor_auth'            => 'Zwei-Faktor-Authentifizierung',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'   => 'Zwei-Faktor-Authentifizierung aktivieren',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType'      => 'Art der Zwei-Faktor-Authentifizierung',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_otp'  => 'OTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_totp' => 'TOTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_both' => 'OTP und TOTP',

    // OTP-E-Mail — wird verwendet, wenn der Notifier im Admin-Kontext läuft
    'OTP_EMAIL_SUBJECT' => 'Ihr Verifizierungscode',
    'OTP_EMAIL_BODY'    => 'Ihr Verifizierungscode lautet: %s',

    // Admin 2FA OTP-Formular
    'OE_SECURITY_ADMIN_TWO_FACTOR_TITLE'       => 'Zwei-Faktor-Authentifizierung',
    'OE_SECURITY_ADMIN_TWO_FACTOR_DESCRIPTION' => 'Ein Bestätigungscode wurde an Ihre E-Mail-Adresse gesendet. Bitte geben Sie ihn unten ein.',
    'OE_SECURITY_ENTER_CODE'                   => 'Bestätigungscode',
    'OE_SECURITY_VERIFY'                       => 'Bestätigen',
    'OE_SECURITY_LOG_IN_AGAIN'                 => 'Erneut anmelden',
    'OE_SECURITY_REMAINING_ATTEMPTS'           => 'Verbleibende Versuche',

    'RESEND_CODE'           => 'Code erneut senden',
    'RESEND_CODE_SENDING'   => 'Wird gesendet…',
    'RESEND_CODE_ERROR'     => 'Code konnte nicht erneut gesendet werden.',
    'RESEND_CODE_COUNTDOWN' => 'Erneut senden in %ds',

    'ERROR_INVALID_CODE'           => 'Der Bestätigungscode ist ungültig. Bitte versuchen Sie es erneut.',
    'ERROR_CODE_TIME_EXPIRED'      => 'Der Bestätigungscode ist abgelaufen. Bitte fordern Sie einen neuen Code an.',
    'ERROR_ATTEMPT_LIMIT_EXCEEDED' => 'Zu viele fehlgeschlagene Versuche. Bitte melden Sie sich erneut an.',
];
