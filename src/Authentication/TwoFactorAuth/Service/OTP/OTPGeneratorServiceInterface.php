<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

interface OTPGeneratorServiceInterface
{
    public function generate(string $userId, int $length): OTP;
}
