<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

interface OTPServiceInterface
{
    public function getOTP(string $userId): OTP;

    public function delete(string $userId): void;
}
