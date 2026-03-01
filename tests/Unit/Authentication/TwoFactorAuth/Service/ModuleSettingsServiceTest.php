<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface as TwoFASettingsInterface;
use OxidEsales\SecurityModule\Core\Module;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ModuleSettingsServiceTest extends TestCase
{
    #[DataProvider('booleanSettingsProvider')]
    public function testBooleanGetters(string $method, string $key, bool $value): void
    {
        $settingService = $this->createMock(ModuleSettingServiceInterface::class);
        $settingService->method('getBoolean')
            ->with($key, Module::MODULE_ID)
            ->willReturn($value);

        $sut = new ModuleSettingsService($settingService);

        $this->assertSame($value, $sut->$method());
    }

    public static function booleanSettingsProvider(): array
    {
        return [
            ['isTwoFactorAuthEnabled', ModuleSettingsService::TWO_FACTOR_AUTH_ENABLED, true],
            ['isTwoFactorAuthEnabled', ModuleSettingsService::TWO_FACTOR_AUTH_ENABLED, false],
        ];
    }

    #[DataProvider('integerSettingsProvider')]
    public function testIntegerGetters(string $method, string $key, int $value): void
    {
        $settingService = $this->createMock(ModuleSettingServiceInterface::class);
        $settingService->method('getInteger')
            ->with($key, Module::MODULE_ID)
            ->willReturn($value);

        $sut = new ModuleSettingsService($settingService);

        $this->assertSame($value, $sut->$method());
    }

    public static function integerSettingsProvider(): array
    {
        return [
            ['getOtpLength', ModuleSettingsService::OTP_LENGTH, 6],
            ['getOtpLength', ModuleSettingsService::OTP_LENGTH, 8],
            ['getOtpTtl', ModuleSettingsService::OTP_TTL, 300],
            ['getOtpMaxAttempts', ModuleSettingsService::OTP_MAX_ATTEMPTS, 5],
            ['getOtpBlockDuration', ModuleSettingsService::OTP_BLOCK_DURATION, 300],
            ['getOtpResendCooldown', ModuleSettingsService::OTP_RESEND_COOLDOWN, 60],
        ];
    }
}
