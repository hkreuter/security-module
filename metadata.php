<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Metadata version
 */

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller\AdminLoginController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller\AdminTwoFactorAuthController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettings as TwoFactorAuthModuleSettings;
use OxidEsales\SecurityModule\PasswordPolicy\Service\ModuleSettingsService as PasswordPolicyModuleSettings;
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
    'version'     => '4.0.0',
    'author'      => 'OXID eSales AG',
    'url'         => 'https://github.com/OXID-eSales/security-module',
    'email'       => 'info@oxid-esales.com',
    'extend'      => [
        \OxidEsales\Eshop\Application\Component\UserComponent::class => \OxidEsales\SecurityModule\Captcha\Shop\UserComponent::class,
        \OxidEsales\Eshop\Application\Controller\NewsletterController::class => \OxidEsales\SecurityModule\Captcha\Shop\NewsletterController::class,
        \OxidEsales\Eshop\Application\Controller\ForgotPasswordController::class => \OxidEsales\SecurityModule\Shared\Controller\ForgotPasswordController::class,
        \OxidEsales\Eshop\Application\Model\User::class => \OxidEsales\SecurityModule\Shared\Model\User::class,
        \OxidEsales\Eshop\Core\InputValidator::class    => \OxidEsales\SecurityModule\Shared\Core\InputValidator::class,
        \OxidEsales\Eshop\Core\ViewConfig::class        => \OxidEsales\SecurityModule\Shared\Core\ViewConfig::class,
        \OxidEsales\Eshop\Application\Controller\Admin\LoginController::class => AdminLoginController::class,
    ],
    'controllers' => [
        'captcha' => \OxidEsales\SecurityModule\Captcha\Controller\CaptchaController::class,
        'password' => \OxidEsales\SecurityModule\PasswordPolicy\Controller\PasswordAjaxController::class
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
    ],
];
