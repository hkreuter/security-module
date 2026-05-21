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

    'SHOP_MODULE_GROUP_form_security'                    => 'Form Security',
    'SHOP_MODULE_oeSecurityGetFormStripStoken'           => 'Strip session token from GET form URLs',
    'HELP_SHOP_MODULE_oeSecurityGetFormStripStoken'      => 'When enabled, session tokens (stoken) are removed from GET form submissions. ' .
        'This module provides coverage for the Apex theme search form, attribute filter, and product list. ' .
        'Custom themes or additional modules can integrate with this setting via ' .
        'oViewConf.getSecurityModuleFormSettings().isGetFormStripStokenEnabled(). ' .
        'POST forms are unaffected.',

    'SHOP_MODULE_GROUP_two_factor_auth'            => 'Two Factor Authentication',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'   => 'Enable Two Factor Authentication',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType'      => 'Two Factor Authentication type',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_otp'  => 'OTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_totp' => 'TOTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_both' => 'OTP and TOTP',
];
