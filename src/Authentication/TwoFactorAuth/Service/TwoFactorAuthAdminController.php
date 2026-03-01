<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Application\Controller\Admin\AdminController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;

class TwoFactorAuthAdminController extends AdminController
{
    protected $_sThisTemplate = 'oe_security_2fa_admin';

    public function handleOTP(): string
    {
        $code = Registry::getRequest()->getRequestParameter('otp_code');
        $sid = Registry::getSession()->getId();

        $repository = $this->getService(OTPRepositoryInterface::class);
        $otp = $repository->findBySid($sid);

        if ($otp === null) {
            return $this->_sThisTemplate;
        }

        $orchestrator = $this->getService(TwoFactorAuthOrchestratorInterface::class);

        if ($orchestrator->verify($otp->userId, (string) $code)) {
            Registry::getSession()->setVariable('auth', $otp->userId);

            return 'admin_start';
        }

        $this->addTplParam('error', 'invalid_code');
        $this->addTplParam(
            'remaining_attempts',
            $orchestrator->getRemainingAttempts($otp->userId)
        );

        return $this->_sThisTemplate;
    }
}
