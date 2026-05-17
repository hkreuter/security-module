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

    'SHOP_MODULE_GROUP_two_factor_auth'            => 'Two Factor Authentication',
    'SHOP_MODULE_oeSecurityTwoFactorAuthEnabled'   => 'Enable Two Factor Authentication',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType'      => 'Two Factor Authentication type',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_otp'  => 'OTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_totp' => 'TOTP',
    'SHOP_MODULE_oeSecurityTwoFactorAuthType_both' => 'OTP and TOTP',

    // OTP email — used when the notifier runs in admin context
    'OTP_EMAIL_SUBJECT' => 'Your verification code',
    'OTP_EMAIL_BODY'    => 'Your verification code is: %s',

    // Admin 2FA OTP form
    'OE_SECURITY_ADMIN_TWO_FACTOR_TITLE'       => 'Two-Factor Authentication',
    'OE_SECURITY_ADMIN_TWO_FACTOR_DESCRIPTION' => 'A verification code has been sent to your email. Please enter it below.',
    'OE_SECURITY_ENTER_CODE'                   => 'Verification code',
    'OE_SECURITY_VERIFY'                       => 'Verify',
    'OE_SECURITY_LOG_IN_AGAIN'                 => 'Log in again',
    'OE_SECURITY_REMAINING_ATTEMPTS'           => 'Remaining attempts',

    'RESEND_CODE'           => 'Resend Code',
    'RESEND_CODE_SENDING'   => 'Sending…',
    'RESEND_CODE_ERROR'     => 'Could not resend code.',
    'RESEND_CODE_COUNTDOWN' => 'Resend in %ds',

    'ERROR_INVALID_CODE'           => 'The verification code is invalid. Please try again.',
    'ERROR_CODE_TIME_EXPIRED'      => 'The verification code has expired. Please request a new code.',
    'ERROR_ATTEMPT_LIMIT_EXCEEDED' => 'Too many failed attempts. Please log in again to start over.',
];
