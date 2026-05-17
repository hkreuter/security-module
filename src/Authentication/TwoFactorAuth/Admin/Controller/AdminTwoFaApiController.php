<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller;

use OxidEsales\EshopCommunity\Internal\Framework\Session\SessionInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service\AdminTwoFaLoginService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\AttemptLimitExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\ResendCooldownException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

readonly class AdminTwoFaApiController
{
    public function __construct(
        private SessionInterface $session,
        private TwoFAServiceInterface $twoFAService,
    ) {
    }

    #[Route('/api/admin-2fa/resend', methods: ['POST'])]
    public function resend(): JsonResponse
    {
        $userId = $this->session->get(AdminTwoFaLoginService::ADMIN_SESSION_KEY);

        if (!$userId) {
            return new JsonResponse(['success' => false], 401);
        }

        if (!$this->twoFAService instanceof TwoFAResendableInterface) {
            return new JsonResponse(['success' => false], 405);
        }

        try {
            $this->twoFAService->resend($userId);

            return new JsonResponse([
                'success' => true,
                'remainingAttempts' => $this->twoFAService->getRemainingAttempts($userId),
                'cooldownRemaining' => $this->twoFAService->getCooldownRemaining($userId),
            ]);
        } catch (AttemptLimitExceededException) {
            return new JsonResponse(['success' => false], 429);
        } catch (ResendCooldownException) {
            return new JsonResponse([
                'success' => false,
                'cooldownRemaining' => $this->twoFAService->getCooldownRemaining($userId),
            ], 429);
        }
    }
}
