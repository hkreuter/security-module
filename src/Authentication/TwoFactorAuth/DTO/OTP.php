<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO;

final class OTP
{
    public function __construct(
        private readonly string $userId,
        private readonly string $code,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly int $attempts,
        private readonly ?\DateTimeImmutable $lastSentAt,
        private readonly string $sid,
        private readonly string $context,
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getLastSentAt(): ?\DateTimeImmutable
    {
        return $this->lastSentAt;
    }

    public function getSid(): string
    {
        return $this->sid;
    }

    public function getContext(): string
    {
        return $this->context;
    }
}
