<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO;

final class OTP
{
    public function __construct(
        public readonly string $userId,
        public readonly string $code,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly int $attempts,
        public readonly ?\DateTimeImmutable $lastSentAt,
        public readonly string $sid,
        public readonly string $context = 'frontend',
    ) {
    }
}
