<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

interface OTPValidatorServiceInterface
{
    public function codeMatches(OTP $otp, string $rawCode): bool;

    public function isExpired(OTP $otp): bool;

    public function maxAttemptsExceeded(OTP $otp): bool;

    public function canResend(OTP $otp): bool;
}
