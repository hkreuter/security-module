<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPServiceInterface;
use Psr\Log\LoggerInterface;

class TwoFactorAuthOrchestrator implements TwoFactorAuthOrchestratorInterface
{
    public function __construct(
        private readonly OTPServiceInterface $otpService,
        private readonly OTPValidatorServiceInterface $otpValidator,
        private readonly OTPAttemptServiceInterface $attemptService,
        private readonly EmailAdapterInterface $emailAdapter,
        private readonly OTPRepositoryInterface $otpRepository,
        private readonly ModuleSettingsServiceInterface $moduleSettings,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function initiate(string $userId, string $email, string $sid, string $context): void
    {
        $this->otpService->delete($userId);
        $otp = $this->otpService->getOTP($userId, $sid, $context);

        $this->emailAdapter->send($email, $otp->getCode());
        $this->logger->info('OTP sent', ['userId' => $userId]);
    }

    public function verify(string $userId, string $inputCode): bool
    {
        $otp = $this->otpRepository->find($userId);

        if ($otp === null) {
            return false;
        }

        if ($this->otpValidator->maxAttemptsExceeded($otp)) {
            return false;
        }

        if ($this->otpValidator->isExpired($otp)) {
            return false;
        }

        if ($this->otpValidator->codeMatches($otp, $inputCode)) {
            $this->otpService->delete($userId);
            $this->logger->info('OTP verified', ['userId' => $userId]);

            return true;
        }

        $this->attemptService->increment($userId);
        $this->logger->warning('OTP verification failed', ['userId' => $userId]);

        return false;
    }

    public function canRetry(string $userId): bool
    {
        return $this->attemptService->canRetry($userId);
    }

    public function getRemainingAttempts(string $userId): int
    {
        $otp = $this->otpRepository->find($userId);

        if ($otp === null) {
            return $this->moduleSettings->getMaxAttempts();
        }

        return max(0, $this->moduleSettings->getMaxAttempts() - $otp->getAttempts());
    }

    public function isBlocked(string $userId): bool
    {
        return !$this->attemptService->canRetry($userId);
    }
}
