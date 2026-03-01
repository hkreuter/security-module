<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\OTPValidationException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPValidatorServiceInterface;
use Psr\Log\LoggerInterface;

class TwoFactorAuthOrchestrator implements TwoFactorAuthOrchestratorInterface
{
    public function __construct(
        private readonly OTPServiceInterface $otpService,
        private readonly OTPValidatorServiceInterface $validator,
        private readonly OTPAttemptServiceInterface $attemptService,
        private readonly EmailAdapterInterface $emailAdapter,
        private readonly OTPRepositoryInterface $repository,
        private readonly ModuleSettingsServiceInterface $settings,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function initiate(string $userId, string $sid, string $context = 'frontend'): void
    {
        $otp = $this->otpService->getOTP($userId);

        $this->logger->info('2FA OTP initiated', [
            'userId' => $userId,
            'context' => $context,
        ]);
    }

    public function sendCode(string $userId, string $email): void
    {
        $otp = $this->otpService->getOTP($userId);
        $this->emailAdapter->send($email, $otp->code);

        $this->logger->info('2FA OTP sent', ['userId' => $userId]);
    }

    public function verify(string $userId, string $inputCode): bool
    {
        $otp = $this->repository->find($userId);

        if ($otp === null) {
            $this->logger->warning('2FA verify: no OTP found', [
                'userId' => $userId,
            ]);
            return false;
        }

        try {
            $this->validator->validate($otp, $inputCode);
            $this->otpService->delete($userId);

            $this->logger->info('2FA OTP verified', ['userId' => $userId]);
            return true;
        } catch (OTPValidationException $e) {
            $this->attemptService->increment($userId);

            $this->logger->info('2FA OTP validation failed', [
                'userId' => $userId,
                'reason' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function canRetry(string $userId): bool
    {
        return $this->attemptService->canRetry($userId);
    }

    public function getRemainingAttempts(string $userId): int
    {
        $otp = $this->repository->find($userId);

        if ($otp === null) {
            return $this->settings->getOtpMaxAttempts();
        }

        return max(0, $this->settings->getOtpMaxAttempts() - $otp->attempts);
    }

    public function isBlocked(string $userId): bool
    {
        $otp = $this->repository->find($userId);

        if ($otp === null) {
            return false;
        }

        return $otp->attempts >= $this->settings->getOtpMaxAttempts();
    }
}
