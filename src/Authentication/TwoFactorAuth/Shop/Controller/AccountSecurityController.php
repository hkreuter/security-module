<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Shop\Controller;

use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAUserSettingsInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\UserSettingsUpdateRequestInterface;

class AccountSecurityController extends AccountController
{
    public function __construct(
        private readonly TwoFAUserSettingsInterface $userSettingsService,
        private readonly UserSettingsUpdateRequestInterface $settingUpdateRequest,
        private readonly UtilsServer $utilsServer,
        private readonly ContextInterface $context,
    ) {
        $this->setTemplateName('@oe_security_module/templates/account_security');
        parent::__construct();
    }

    public function render(): string
    {
        $parentResult = parent::render();

        $user = $this->getUser();
        if ($user) {
            $this->addTplParam('twoFAEnabledForUser', $this->userSettingsService->isEnabledForUser($user->getId()));
        }

        return $parentResult;
    }

    public function saveTwoFactorAuth(): void
    {
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        $twoFAEnabled = $this->settingUpdateRequest->isTwoFAEnabled();

        $this->userSettingsService->setEnabledForUser($user->getId(), $twoFAEnabled);

        if ($twoFAEnabled) {
            $this->utilsServer->deleteUserCookie((string)$this->context->getCurrentShopId());
        }

        $this->addTplParam('twoFASaved', true);
    }
}
