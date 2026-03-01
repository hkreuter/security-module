<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\FrontendController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;

class TwoFactorAuthController extends FrontendController
{
    public function handleOTP(): void
    {
        $code = Registry::getRequest()->getRequestParameter('otp_code');
        $sid = Registry::getSession()->getId();

        $repository = $this->getService(OTPRepositoryInterface::class);
        $otp = $repository->findBySid($sid);

        if ($otp === null) {
            $this->sendJsonResponse(['success' => false, 'error' => 'no_pending_otp']);
            return;
        }

        $orchestrator = $this->getService(TwoFactorAuthOrchestratorInterface::class);

        if ($orchestrator->verify($otp->userId, (string) $code)) {
            Registry::getSession()->setVariable('usr', $otp->userId);
            Registry::getSession()->regenerateSessionId();

            $this->sendJsonResponse(['success' => true]);
        } else {
            $remaining = $orchestrator->getRemainingAttempts($otp->userId);
            $blocked = $orchestrator->isBlocked($otp->userId);

            $this->sendJsonResponse([
                'success' => false,
                'error' => $blocked ? 'blocked' : 'invalid_code',
                'remaining_attempts' => $remaining,
            ]);
        }
    }

    public function resendOTP(): void
    {
        $sid = Registry::getSession()->getId();

        $repository = $this->getService(OTPRepositoryInterface::class);
        $otp = $repository->findBySid($sid);

        if ($otp === null) {
            $this->sendJsonResponse(['success' => false, 'error' => 'no_pending_otp']);
            return;
        }

        $orchestrator = $this->getService(TwoFactorAuthOrchestratorInterface::class);
        $orchestrator->initiate($otp->userId, $sid, $otp->context);

        $this->sendJsonResponse(['success' => true]);
    }

    private function sendJsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        Registry::getUtils()->showMessageAndExit(json_encode($data));
    }
}
