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

    'SHOP_MODULE_GROUP_two_factor_auth'                 => 'Two Factor Authentication',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'        => 'Enable Two Factor Authentication',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'   => 'Enable two-factor authentication shop-wide. '
        . 'When active, users who have 2FA enabled on their account must verify a code during login.',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType'           => 'Two Factor Authentication type',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthType'      => 'Method used to deliver the second factor. '
        . 'Currently only "otp" (one-time password sent by email) is supported. TOTP (authenticator-app) '
        . 'is planned for a future release.',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_otp'       => 'OTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_totp'      => 'TOTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_both'      => 'OTP and TOTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthApiChallengeLifetime'      => 'API 2FA challenge lifetime (seconds)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthApiChallengeLifetime' => 'How long an API 2FA challenge '
        . 'Bearer remains exchangeable for a full access token, in seconds. The effective lifetime '
        . 'is automatically capped at the OTP code lifetime (see below) — a Bearer is never useful '
        . 'longer than the OTP itself. Default: 300 (5 minutes).',
    'SHOP_MODULE_oeSecurityTwoFactorAuthOtpCodeLifetime'      => 'OTP code lifetime (seconds)',
    'HELP_SHOP_MODULE_oeSecurityTwoFactorAuthOtpCodeLifetime' => 'How long an emailed OTP code remains '
        . 'valid, in seconds. Applies to both the storefront/admin and API 2FA flows. Acts as the '
        . 'upper bound for the API challenge lifetime above. Default: 300 (5 minutes).',

    'SHOP_MODULE_GROUP_password_reuse'                          => 'Password Reuse Prevention',
    'SHOP_MODULE_oeSecurityPasswordReuseEnable'                 => 'Enable password reuse prevention',
    'HELP_SHOP_MODULE_oeSecurityPasswordReuseEnable'            => 'Reject a new password that matches any '
        . 'of the account\'s recently used passwords. When disabled, no reuse check runs and the stored '
        . 'password history is purged. Off by default.',
    'SHOP_MODULE_oeSecurityPasswordChangeNotificationEnable'    => 'Enable password change notification email',
    'HELP_SHOP_MODULE_oeSecurityPasswordChangeNotificationEnable' => 'Send the affected account a security '
        . 'notification email after every successful password change or reset. Off by default.',
    'SHOP_MODULE_oeSecurityPasswordReuseCustomerSize'           => 'Remembered passwords (customer accounts)',
    'HELP_SHOP_MODULE_oeSecurityPasswordReuseCustomerSize'      => 'How many recent passwords a customer '
        . 'account may not reuse (including the current one). Default: 5.',
    'SHOP_MODULE_oeSecurityPasswordReuseCustomerSize_3'         => '3',
    'SHOP_MODULE_oeSecurityPasswordReuseCustomerSize_5'         => '5',
    'SHOP_MODULE_oeSecurityPasswordReuseCustomerSize_10'        => '10',
    'SHOP_MODULE_oeSecurityPasswordReuseAdminSize'              => 'Remembered passwords (admin accounts)',
    'HELP_SHOP_MODULE_oeSecurityPasswordReuseAdminSize'         => 'How many recent passwords an admin '
        . 'account may not reuse (including the current one). Treated as at least the customer value at '
        . 'runtime. Default: 10.',
    'SHOP_MODULE_oeSecurityPasswordReuseAdminSize_5'            => '5',
    'SHOP_MODULE_oeSecurityPasswordReuseAdminSize_10'           => '10',
    'SHOP_MODULE_oeSecurityPasswordReuseAdminSize_24'           => '24',
];
