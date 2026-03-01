<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

interface ModuleSettingsServiceInterface
{
    public function isTwoFactorAuthEnabled(): bool;

    public function getOtpLength(): int;

    public function getOtpTtl(): int;

    public function getOtpMaxAttempts(): int;

    public function getOtpBlockDuration(): int;

    public function getOtpResendCooldown(): int;
}
