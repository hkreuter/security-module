<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;

class ModuleSettingsService implements ModuleSettingsServiceInterface
{
    public const TWO_FACTOR_AUTH_ENABLE = 'oeSecurityTwoFactorAuthEnable';
    public const OTP_LENGTH = 'oeSecurityTwoFactorAuthOtpLength';
    public const OTP_LIFETIME = 'oeSecurityTwoFactorAuthOtpLifetime';
    public const MAX_ATTEMPTS = 'oeSecurityTwoFactorAuthMaxAttempts';
    public const COOLDOWN = 'oeSecurityTwoFactorAuthCooldown';

    public function __construct(
        private readonly ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function isTwoFactorAuthEnabled(): bool
    {
        return $this->moduleSettingService->getBoolean(
            self::TWO_FACTOR_AUTH_ENABLE,
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

    public function getOtpLifetime(): int
    {
        return $this->moduleSettingService->getInteger(
            self::OTP_LIFETIME,
            Module::MODULE_ID
        );
    }

    public function getMaxAttempts(): int
    {
        return $this->moduleSettingService->getInteger(
            self::MAX_ATTEMPTS,
            Module::MODULE_ID
        );
    }

    public function getCooldown(): int
    {
        return $this->moduleSettingService->getInteger(
            self::COOLDOWN,
            Module::MODULE_ID
        );
    }
}
