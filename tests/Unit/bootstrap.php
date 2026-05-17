<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

/**
 * PHPUnit bootstrap for unit tests.
 *
 * Provides _parent class aliases required by OXID's class-chain mechanism.
 * These aliases resolve the `Foo_parent` placeholder that OXID creates at
 * runtime (via the unified namespace generator) when tests run without the
 * full OXID bootstrap.
 */

// Existing shared-model and controller _parent aliases
class_alias(
    \OxidEsales\Eshop\Application\Model\User::class,
    \OxidEsales\SecurityModule\Shared\Model\User_parent::class
);

class_alias(
    \OxidEsales\Eshop\Core\InputValidator::class,
    \OxidEsales\SecurityModule\Shared\Core\InputValidator_parent::class
);

class_alias(
    \OxidEsales\Eshop\Core\ViewConfig::class,
    \OxidEsales\SecurityModule\Shared\Core\ViewConfig_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Component\UserComponent::class,
    \OxidEsales\SecurityModule\Captcha\Shop\UserComponent_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\NewsletterController::class,
    \OxidEsales\SecurityModule\Captcha\Shop\NewsletterController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\ForgotPasswordController::class,
    \OxidEsales\SecurityModule\Shared\Controller\ForgotPasswordController_parent::class
);

// Admin 2FA _parent aliases
class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\LoginController::class,
    \OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller\AdminLoginController_parent::class
);
