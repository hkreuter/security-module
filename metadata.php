<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Metadata version
 */

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettings as TwoFactorAuthModuleSettings;
use OxidEsales\SecurityModule\PasswordPolicy\Service\ModuleSettingsService as PasswordPolicyModuleSettings;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService as PasswordReuseModuleSettings;
use OxidEsales\SecurityModule\Captcha\Service\ModuleSettingsService as CaptchaModuleSettings;
use OxidEsales\SecurityModule\Core\Module;

$sMetadataVersion = '2.1';

/**
 * Module information
 */
$aModule = [
    'id'          => Module::MODULE_ID,
    'title'       => 'OXID Security Module',
    'description' => [
        'en' => 'Tools to protect your shop and safeguard customer accounts.',
        'de' => 'Werkzeuge zum Schutz Ihres Shops und zur Sicherung von Kundenkonten.'
    ],
    'thumbnail'   => 'logo.png',
    'version'     => '4.1.0',
    'author'      => 'OXID eSales AG',
    'url'         => 'https://github.com/OXID-eSales/security-module',
    'email'       => 'info@oxid-esales.com',
    'extend'      => [
        \OxidEsales\Eshop\Application\Component\UserComponent::class => \OxidEsales\SecurityModule\Captcha\Shop\UserComponent::class,
        \OxidEsales\Eshop\Application\Controller\NewsletterController::class => \OxidEsales\SecurityModule\Captcha\Shop\NewsletterController::class,
        \OxidEsales\Eshop\Application\Controller\AccountPasswordController::class => \OxidEsales\SecurityModule\Shared\Controller\AccountPasswordController::class,
        \OxidEsales\Eshop\Application\Controller\ForgotPasswordController::class => \OxidEsales\SecurityModule\Shared\Controller\ForgotPasswordController::class,
        \OxidEsales\Eshop\Application\Controller\Admin\UserMain::class => \OxidEsales\SecurityModule\Shared\Controller\Admin\UserMain::class,
        \OxidEsales\Eshop\Application\Model\User::class => \OxidEsales\SecurityModule\Shared\Model\User::class,
        \OxidEsales\Eshop\Core\InputValidator::class    => \OxidEsales\SecurityModule\Shared\Core\InputValidator::class,
        \OxidEsales\Eshop\Core\ViewConfig::class        => \OxidEsales\SecurityModule\Shared\Core\ViewConfig::class
    ],
    'controllers' => [
        'captcha' => \OxidEsales\SecurityModule\Captcha\Controller\CaptchaController::class,
        'password' => \OxidEsales\SecurityModule\PasswordPolicy\Controller\PasswordAjaxController::class,
    ],
    'templates'   => [
    ],
    'events'      => [
    ],
    'blocks'      => [
    ],
    'settings'    => [
        //Password policy enable
        [
            'group' => 'password_policy',
            'name'  => PasswordPolicyModuleSettings::PASSWORD_POLICY_ENABLE,
            'type'  => 'bool',
            'value' => true
        ],

        //Password length requirements
        [
            'group' => 'password_policy',
            'name'  => PasswordPolicyModuleSettings::PASSWORD_MINIMUM_LENGTH,
            'type'  => 'num',
            'value' => 8
        ],

        //Password symbols requirements
        [
            'group' => 'password_policy',
            'name'  => PasswordPolicyModuleSettings::PASSWORD_UPPERCASE,
            'type'  => 'bool',
            'value' => true
        ],
        [
            'group' => 'password_policy',
            'name'  => PasswordPolicyModuleSettings::PASSWORD_LOWERCASE,
            'type'  => 'bool',
            'value' => true
        ],
        [
            'group' => 'password_policy',
            'name'  => PasswordPolicyModuleSettings::PASSWORD_DIGIT,
            'type'  => 'bool',
            'value' => true
        ],
        [
            'group' => 'password_policy',
            'name'  => PasswordPolicyModuleSettings::PASSWORD_SPECIAL_CHAR,
            'type'  => 'bool',
            'value' => true
        ],

        //Captcha
        [
            'group' => 'captcha',
            'name'  => CaptchaModuleSettings::CAPTCHA_ENABLE,
            'type'  => 'bool',
            'value' => true
        ],
        [
            'group' => 'captcha',
            'name'  => CaptchaModuleSettings::HONEYPOT_CAPTCHA_ENABLE,
            'type'  => 'bool',
            'value' => true
        ],
        [
            'group' => 'captcha',
            'name'  => CaptchaModuleSettings::CAPTCHA_LIFETIME,
            'type'  => 'select',
            'constraints' => '5min|15min|30min',
            'value' => '15min'
        ],

        //TwoFactorAuth settings
        [
            'group' => 'two_factor_auth',
            'name'  => TwoFactorAuthModuleSettings::ACTIVE,
            'type'  => 'bool',
            'value' => false
        ],
        [
            //todo-high: should be moved to the user settings (near by 2FA turning on switch)
            'group' => 'two_factor_auth',
            'name'  => TwoFactorAuthModuleSettings::TWO_FACTOR_TYPE,
            'type'  => 'select',
            'constraints' => 'otp',
            'value' => 'otp'
        ],
        [
            // Lifetime (seconds) of the oxapi 2FA challenge token; should cover the OTP window.
            'group' => 'two_factor_auth',
            'name'  => TwoFactorAuthModuleSettings::API_CHALLENGE_LIFETIME,
            'type'  => 'num',
            'value' => 300
        ],
        [
            // Lifetime (seconds) of the emailed OTP code. Upper bound for the API challenge lifetime.
            'group' => 'two_factor_auth',
            'name'  => TwoFactorAuthModuleSettings::OTP_CODE_LIFETIME,
            'type'  => 'num',
            'value' => 300
        ],

        //Password reuse prevention + change notification (opt-in, off by default)
        [
            'group' => 'password_reuse',
            'name'  => PasswordReuseModuleSettings::REUSE_PREVENTION_ENABLE,
            'type'  => 'bool',
            'value' => false
        ],
        [
            'group' => 'password_reuse',
            'name'  => PasswordReuseModuleSettings::CHANGE_NOTIFICATION_ENABLE,
            'type'  => 'bool',
            'value' => false
        ],
        [
            'group' => 'password_reuse',
            'name'  => PasswordReuseModuleSettings::CUSTOMER_COLLECTION_SIZE,
            'type'  => 'select',
            'constraints' => '3|5|10',
            'value' => '5'
        ],
        [
            'group' => 'password_reuse',
            'name'  => PasswordReuseModuleSettings::ADMIN_COLLECTION_SIZE,
            'type'  => 'select',
            'constraints' => '5|10|24',
            'value' => '10'
        ],
    ],
];
