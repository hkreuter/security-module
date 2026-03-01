<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;

class OTPAttemptService implements OTPAttemptServiceInterface
{
    public function __construct(
        private readonly OTPRepositoryInterface $repository,
        private readonly ModuleSettingsServiceInterface $settings,
    ) {
    }

    public function increment(string $userId): void
    {
        $this->repository->incrementAttempts($userId);
    }

    public function canRetry(string $userId): bool
    {
        $otp = $this->repository->find($userId);

        if ($otp === null) {
            return true;
        }

        return $otp->attempts < $this->settings->getOtpMaxAttempts();
    }
}
