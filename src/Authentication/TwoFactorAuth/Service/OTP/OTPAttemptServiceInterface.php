<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

interface OTPAttemptServiceInterface
{
    public function increment(string $userId): void;

    public function canRetry(string $userId): bool;
}
