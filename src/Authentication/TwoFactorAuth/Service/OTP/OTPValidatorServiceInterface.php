<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

interface OTPValidatorServiceInterface
{
    /**
     * @throws \OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\MaxAttemptsExceededException
     * @throws \OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\OTPExpiredException
     * @throws \OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException
     */
    public function validate(OTP $otp, string $inputCode): void;
}
