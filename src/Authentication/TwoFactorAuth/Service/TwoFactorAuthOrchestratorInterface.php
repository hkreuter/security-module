<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

interface TwoFactorAuthOrchestratorInterface
{
    public function initiate(string $userId, string $email, string $sid, string $context): void;

    public function verify(string $userId, string $inputCode): bool;

    public function canRetry(string $userId): bool;

    public function getRemainingAttempts(string $userId): int;

    public function isBlocked(string $userId): bool;
}
