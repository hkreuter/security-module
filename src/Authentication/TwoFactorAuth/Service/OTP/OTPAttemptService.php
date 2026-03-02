<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;

class OTPAttemptService implements OTPAttemptServiceInterface
{
    public function __construct(
        private readonly OTPRepositoryInterface $otpRepository,
        private readonly ModuleSettingsServiceInterface $moduleSettingsService,
    ) {
    }

    public function increment(string $userId): void
    {
        $this->otpRepository->incrementAttempts($userId);
    }

    public function canRetry(string $userId): bool
    {
        $otp = $this->otpRepository->find($userId);

        if ($otp === null) {
            return true;
        }

        return $otp->getAttempts() < $this->moduleSettingsService->getMaxAttempts();
    }
}
