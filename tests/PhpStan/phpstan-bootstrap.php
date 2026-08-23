<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

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
    \OxidEsales\Eshop\Application\Model\User::class,
    \OxidEsales\SecurityModule\Shared\Model\User_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\NewsletterController::class,
    \OxidEsales\SecurityModule\Captcha\Shop\NewsletterController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\ForgotPasswordController::class,
    \OxidEsales\SecurityModule\Shared\Controller\ForgotPasswordController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\AccountPasswordController::class,
    \OxidEsales\SecurityModule\Shared\Controller\AccountPasswordController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\UserMain::class,
    \OxidEsales\SecurityModule\Shared\Controller\Admin\UserMain_parent::class
);
