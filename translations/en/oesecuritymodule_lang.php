<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = 'English';

$aLang = [
    'charset' => 'UTF-8',

    'OESECURITYMODULE_PASSWORD_RECENTLY_USED'    => 'This password was used recently. Please choose a different one.',
    'OESECURITYMODULE_PASSWORD_REUSE_CHECK_FAILED' => 'Your password could not be changed right now. Please try again later.',

    'ERROR_PASSWORD_MIN_LENGTH'                => 'The password must be at least %d characters long.',
    'ERROR_PASSWORD_MISSING_DIGIT'             => 'The password does not contain a number.',
    'ERROR_PASSWORD_MISSING_LOWER_CASE'        => 'The password does not contain a lower case letter.',
    'ERROR_PASSWORD_MISSING_UPPER_CASE'        => 'The password does not contain a capital letter.',
    'ERROR_PASSWORD_MISSING_SPECIAL_CHARACTER' => 'The password does not contain a special character.',

    'CAPTCHA_INPUT_LABEL'                      => 'Enter the text from the image',
    'CAPTCHA_IMAGE_ALT_TEXT'                   => 'Image containing CAPTCHA code',
    'CAPTCHA_RELOAD'                           => 'Reload Captcha',
    'CAPTCHA_PLAY_AUDIO'                       => 'Play Captcha Audio',
    'ERROR_EXPIRED_CAPTCHA'                    => 'CAPTCHA has expired.',
    'ERROR_INVALID_CAPTCHA'                    => 'CAPTCHA text is invalid.',
    'ERROR_EMPTY_CAPTCHA'                      => 'CAPTCHA field is empty. Please, enter the text from the image.',
    'FORM_VALIDATION_FAILED'                   => 'Unexpected error occurred. Please refresh and try again.',

    'PASSWORD_REQUIREMENTS'              => 'Password requirements',
    'PASSWORD_MIN_LENGTH'                => 'Minimum %d characters.',
    'PASSWORD_CONTAIN_DIGIT'             => 'At least one number.',
    'PASSWORD_CONTAIN_LOWER_CASE'        => 'At least one lower case letter.',
    'PASSWORD_CONTAIN_UPPER_CASE'        => 'At least one upper case letter.',
    'PASSWORD_CONTAIN_SPECIAL_CHARACTER' => 'At least one special character.',

    'ERROR_PASSWORD_STRENGTH_0' => "Very weak",
    'ERROR_PASSWORD_STRENGTH_1' => "Weak",
    'ERROR_PASSWORD_STRENGTH_2' => "Medium",
    'ERROR_PASSWORD_STRENGTH_3' => "Strong",
    'ERROR_PASSWORD_STRENGTH_4' => "Very strong",

    'GENERATE_STRONG_PASSWORD' => 'Generate Strong Password',

    'SIGN_IN_PROVIDER' => 'Sign in with %s',

    'OTP_EMAIL_SUBJECT' => 'Your OXID eShop verification code',
    'OTP_EMAIL_BODY'    => "Hello,\n\n"
        . "Someone (hopefully you) is trying to log in to your OXID eShop account.\n\n"
        . "Your verification code is: %s\n\n"
        . "This code expires in %d minutes and can only be used once.\n\n"
        . "If you didn't try to log in, you can safely ignore this email — but we'd recommend"
        . " changing your password just to be safe.\n\n"
        . "Your OXID eShop team.",

    'OESM_PASSWORDCHANGE_EMAIL_SUBJECT' => 'Your OXID eShop password was changed',
    'OESM_PASSWORDCHANGE_EMAIL_BODY'    => "Hello,\n\n"
        . "the password for your OXID eShop account was changed on %s.\n\n"
        . "If you made this change, there is nothing more you need to do.\n\n"
        . "If this wasn't you, please contact our support immediately and reset your password.\n\n"
        . "Your OXID eShop team.",

    'TWO_FACTOR_AUTHENTICATION_TITLE'       => 'Two Factor Authentication',
    'TWO_FACTOR_AUTHENTICATION_DESCRIPTION' => 'Code has been sent to your email. Please enter it below to proceed.',

    'RESEND_CODE'           => 'Resend Code',
    'RESEND_CODE_SENDING'   => 'Sending…',
    'RESEND_CODE_ERROR'     => 'Could not resend code.',
    'RESEND_CODE_COUNTDOWN' => 'Resend in %ds',

    'ERROR_INVALID_CODE'           => 'The verification code is invalid. Please try again.',
    'ERROR_CODE_TIME_EXPIRED'      => 'The verification code has expired. Please request a new code.',
    'ERROR_ATTEMPT_LIMIT_EXCEEDED' => 'Too many failed attempts. Please log in again to start over.',
    'ERROR_SESSION_EXPIRED'        => 'Your session has expired. Please log in again.',

    'OE_SECURITY_REMAINING_ATTEMPTS' => 'Remaining attempts',
    'OE_SECURITY_ENTER_CODE'         => 'Verification code',
    'OE_SECURITY_LOG_IN_AGAIN'       => 'Log in again',

    'OE_SECURITY_EXTERNAL_AUTH_PASSWORD_INFO' => 'You are signed in with an external provider. Password management is not available for this account.',
    'OE_SECURITY_RESET_PASSWORD'              => 'Reset password',

    'OE_SECURITY_SECURITY_TITLE'                  => 'Security',
    'OE_SECURITY_TWO_FACTOR_SETTINGS_TITLE'       => 'Two-Factor Authentication',
    'OE_SECURITY_TWO_FACTOR_SETTINGS_DESCRIPTION' => 'Add an extra layer of security to your account. When enabled, you will need to enter a verification code sent to your email each time you log in.',
    'OE_SECURITY_TWO_FACTOR_ENABLE'               => 'Enable two-factor authentication',
    'OE_SECURITY_TWO_FA_SETTINGS_SAVED'           => 'Two-factor authentication settings have been saved.',
];
