<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Shop\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\AttemptLimitExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\CodeValidationException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\ResendCooldownException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\SessionExpiredException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAUserServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\AuthCodeRequestInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\JsonResponseInterface;

class TwoFactorAuthController extends FrontendController
{
    /**
     * Current view template
     *
     * @var string
     * @SuppressWarnings("PHPMD.CamelCasePropertyName")
     */
    protected $_sThisTemplate = '@oe_security_module/templates/two_factor_auth';

    public function __construct(
        private readonly TwoFAServiceInterface $twoFAService,
        private readonly TwoFAUserServiceInterface $twoFAUserService,
        private readonly AuthCodeRequestInterface $authCodeRequest,
        private readonly UtilsView $utilsView,
        private readonly JsonResponseInterface $jsonResponse,
        private readonly Utils $utils,
        private readonly Config $config,
    ) {
        parent::__construct();
    }

    public function render(): string
    {
        parent::render();

        $userId = $this->twoFAUserService->getPendingUserId();

        if (!$userId) {
            $this->utilsView->addErrorToDisplay(new SessionExpiredException());
            $this->utils->redirect($this->config->getShopHomeUrl(), false);

            return $this->_sThisTemplate;
        }

        if ($this->twoFAService instanceof TwoFAResendableInterface) {
            $this->addTplParam('resendable', true);
            $this->addTplParam('remainingAttempts', $this->twoFAService->getRemainingAttempts($userId));
            $this->addTplParam('resendCooldownRemaining', $this->twoFAService->getCooldownRemaining($userId));
        }

        return $this->_sThisTemplate;
    }

    public function verifyCode(): ?string
    {
        $userId = $this->twoFAUserService->getPendingUserId();
        if ($userId === null) {
            $this->utilsView->addErrorToDisplay(new SessionExpiredException());
            return null;
        }

        $code = $this->authCodeRequest->getCode();

        try {
            $this->twoFAService->verify($userId, $code);
            $this->twoFAUserService->loginUser($userId);
        } catch (CodeValidationException $e) {
            $this->utilsView->addErrorToDisplay($e);
        }

        return null;
    }

    public function abandonChallenge(): void
    {
        $userId = $this->twoFAUserService->getPendingUserId();
        if ($userId !== null) {
            $this->twoFAUserService->abandonChallenge($userId);
        }
    }

    public function resendCode(): void
    {
        if (!$this->twoFAService instanceof TwoFAResendableInterface) {
            $this->jsonResponse->send(['success' => false], 405);
            return;
        }

        $userId = $this->twoFAUserService->getPendingUserId();
        if ($userId === null) {
            $this->jsonResponse->send(['success' => false], 401);
            return;
        }

        try {
            $this->twoFAService->resend($userId);
            $this->jsonResponse->send([
                'success' => true,
                'remainingAttempts' => $this->twoFAService->getRemainingAttempts($userId),
            ]);
        } catch (AttemptLimitExceededException | ResendCooldownException) {
            $this->jsonResponse->send(['success' => false], 429);
        }
    }
}
