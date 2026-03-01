<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

class OTPValidatorService implements OTPValidatorServiceInterface
{
    public function __construct(
        private readonly ModuleSettingsServiceInterface $moduleSettingsService,
    ) {
    }

    public function codeMatches(OTP $otp, string $rawCode): bool
    {
        $expectedHash = hash('sha256', $rawCode . $otp->getUserId());

        return hash_equals($otp->getCode(), $expectedHash);
    }

    public function isExpired(OTP $otp): bool
    {
        return $otp->getExpiresAt() <= new \DateTimeImmutable();
    }

    public function maxAttemptsExceeded(OTP $otp): bool
    {
        return $otp->getAttempts() >= $this->moduleSettingsService->getMaxAttempts();
    }

    public function canResend(OTP $otp): bool
    {
        $lastSentAt = $otp->getLastSentAt();
        if ($lastSentAt === null) {
            return true;
        }

        $cooldown = $this->moduleSettingsService->getCooldown();
        $nextAllowed = $lastSentAt->modify("+{$cooldown} seconds");

        return $nextAllowed <= new \DateTimeImmutable();
    }
}
