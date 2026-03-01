<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = 'English';

$aLang = [
    'SHOP_MODULE_GROUP_password_policy'                     => 'Password Policy',
    'SHOP_MODULE_oeSecurityPasswordEnable'                  => 'Enable Password Policy',
    'SHOP_MODULE_oeSecurityPasswordMinimumLength'           => 'Minimum length',
    'HELP_SHOP_MODULE_oeSecurityPasswordMinimumLength'      => 'Minimum password length. Should be greater than 8. ' .
        'In case the number is less than in shop settings, ' .
        'then the shop default minimum password length will override this value.',
    'SHOP_MODULE_oeSecurityPasswordContainUppercase'        => 'Password must contain at least one uppercase letter',
    'SHOP_MODULE_oeSecurityPasswordContainLowercase'        => 'Password must contain at least one lowercase letter',
    'SHOP_MODULE_oeSecurityPasswordContainDigit'            => 'Password must contain at least one digit',
    'SHOP_MODULE_oeSecurityPasswordContainSpecialCharacter' => 'Password must contain at least one special char',

    'SHOP_MODULE_GROUP_captcha'                   => 'Captcha settings',
    'SHOP_MODULE_oeSecurityCaptchaEnable'         => 'Enable Captcha Security',
    'SHOP_MODULE_oeSecurityHoneyPotCaptchaEnable' => 'Enable HoneyPot captcha',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime'       => 'Captcha lifetime',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime_5min'  => '5 min',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime_15min' => '15 min',
    'SHOP_MODULE_oeSecurityCaptchaLifeTime_30min' => '30 min',

    'SHOP_MODULE_GROUP_two_factor_auth'                       => 'Two-Factor Authentication (2FA)',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnable'               => 'Enable Two-Factor Authentication',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthEnable'          => 'When enabled, users will receive an email with a one-time code during login.',
    'SHOP_MODULE_oeSecurityTwoFactorAuthOtpLength'            => 'OTP code length',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthOtpLength'       => 'Number of digits for the one-time code (default: 6).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthOtpLifetime'          => 'OTP lifetime (seconds)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthOtpLifetime'     => 'How long the one-time code remains valid in seconds (default: 300).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthMaxAttempts'           => 'Max verification attempts',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthMaxAttempts'      => 'Maximum number of failed attempts before the code is invalidated (default: 3).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthCooldown'             => 'Resend cooldown (seconds)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthCooldown'        => 'Minimum time in seconds before a new code can be sent (default: 60).',
];
