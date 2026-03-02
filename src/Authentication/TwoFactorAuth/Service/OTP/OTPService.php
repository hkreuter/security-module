<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ExpirationServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPGeneratorServiceInterface;

class OTPService implements OTPServiceInterface
{
    public function __construct(
        private readonly OTPRepositoryInterface $otpRepository,
        private readonly OTPGeneratorServiceInterface $otpGeneratorService,
        private readonly ExpirationServiceInterface $expirationService,
    ) {
    }

    public function getOTP(string $userId, string $sid, string $context): OTP
    {
        $existing = $this->otpRepository->find($userId);

        if ($existing !== null && $existing->getExpiresAt() > new \DateTimeImmutable()) {
            return $existing;
        }

        if ($existing !== null) {
            $this->otpRepository->delete($userId);
        }

        $rawCode = $this->otpGeneratorService->generate();
        $expiresAt = $this->expirationService->calculate();

        $otp = new OTP(
            userId: $userId,
            code: $rawCode,
            expiresAt: $expiresAt,
            attempts: 0,
            lastSentAt: null,
            sid: $sid,
            context: $context,
        );

        $this->otpRepository->save($otp, $rawCode);

        return $otp;
    }

    public function delete(string $userId): void
    {
        $this->otpRepository->delete($userId);
    }
}
