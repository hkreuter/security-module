<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\MaxAttemptsExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\OTPExpiredException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;

class OTPValidatorService implements OTPValidatorServiceInterface
{
    public function __construct(
        private readonly ModuleSettingsServiceInterface $settings,
    ) {
    }

    public function validate(OTP $otp, string $inputCode): void
    {
        if ($otp->attempts >= $this->settings->getOtpMaxAttempts()) {
            throw new MaxAttemptsExceededException('Maximum OTP attempts exceeded');
        }

        if ($otp->expiresAt <= new \DateTimeImmutable()) {
            throw new OTPExpiredException('OTP has expired');
        }

        $hashedInput = hash('sha256', $inputCode . $otp->userId);
        if (!hash_equals($otp->code, $hashedInput)) {
            throw new InvalidCodeException('Invalid OTP code');
        }
    }
}
