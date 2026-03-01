<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;

class ModuleSettingsService implements ModuleSettingsServiceInterface
{
    public const TWO_FACTOR_AUTH_ENABLED = 'oeSecurityTwoFactorAuthEnabled';
    public const OTP_LENGTH = 'oeSecurityOtpLength';
    public const OTP_TTL = 'oeSecurityOtpTtl';
    public const OTP_MAX_ATTEMPTS = 'oeSecurityOtpMaxAttempts';
    public const OTP_BLOCK_DURATION = 'oeSecurityOtpBlockDuration';
    public const OTP_RESEND_COOLDOWN = 'oeSecurityOtpResendCooldown';

    public function __construct(
        private readonly ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function isTwoFactorAuthEnabled(): bool
    {
        return $this->moduleSettingService->getBoolean(
            self::TWO_FACTOR_AUTH_ENABLED,
            Module::MODULE_ID
        );
    }

    public function getOtpLength(): int
    {
        return $this->moduleSettingService->getInteger(
            self::OTP_LENGTH,
            Module::MODULE_ID
        );
    }

    public function getOtpTtl(): int
    {
        return $this->moduleSettingService->getInteger(
            self::OTP_TTL,
            Module::MODULE_ID
        );
    }

    public function getOtpMaxAttempts(): int
    {
        return $this->moduleSettingService->getInteger(
            self::OTP_MAX_ATTEMPTS,
            Module::MODULE_ID
        );
    }

    public function getOtpBlockDuration(): int
    {
        return $this->moduleSettingService->getInteger(
            self::OTP_BLOCK_DURATION,
            Module::MODULE_ID
        );
    }

    public function getOtpResendCooldown(): int
    {
        return $this->moduleSettingService->getInteger(
            self::OTP_RESEND_COOLDOWN,
            Module::MODULE_ID
        );
    }
}
