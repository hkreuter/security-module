<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

interface OTPRepositoryInterface
{
    public function find(string $userId): ?OTP;

    public function findBySid(string $sid): ?OTP;

    public function save(OTP $otp, string $rawCode): void;

    public function delete(string $userId): void;

    public function incrementAttempts(string $userId): void;
}
