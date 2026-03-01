<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface
    as TwoFAModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Core\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ModuleSettingsService::class)]
final class ModuleSettingsServiceTest extends TestCase
{
    #[DataProvider('booleanGettersDataProvider')]
    public function testBooleanGetters(
        string $method,
        string $key,
        bool $value,
    ): void {
        $settingService = $this->createMock(ModuleSettingServiceInterface::class);
        $settingService->method('getBoolean')
            ->with($key, Module::MODULE_ID)
            ->willReturn($value);

        $sut = new ModuleSettingsService($settingService);

        $this->assertSame($value, $sut->$method());
    }

    public static function booleanGettersDataProvider(): array
    {
        return [
            'enabled true' => [
                'isTwoFactorAuthEnabled',
                ModuleSettingsService::TWO_FACTOR_AUTH_ENABLE,
                true,
            ],
            'enabled false' => [
                'isTwoFactorAuthEnabled',
                ModuleSettingsService::TWO_FACTOR_AUTH_ENABLE,
                false,
            ],
        ];
    }

    #[DataProvider('integerGettersDataProvider')]
    public function testIntegerGetters(
        string $method,
        string $key,
        int $value,
    ): void {
        $settingService = $this->createMock(ModuleSettingServiceInterface::class);
        $settingService->method('getInteger')
            ->with($key, Module::MODULE_ID)
            ->willReturn($value);

        $sut = new ModuleSettingsService($settingService);

        $this->assertSame($value, $sut->$method());
    }

    public static function integerGettersDataProvider(): array
    {
        return [
            'otp length' => [
                'getOtpLength',
                ModuleSettingsService::OTP_LENGTH,
                6,
            ],
            'otp lifetime' => [
                'getOtpLifetime',
                ModuleSettingsService::OTP_LIFETIME,
                300,
            ],
            'max attempts' => [
                'getMaxAttempts',
                ModuleSettingsService::MAX_ATTEMPTS,
                3,
            ],
            'cooldown' => [
                'getCooldown',
                ModuleSettingsService::COOLDOWN,
                60,
            ],
        ];
    }
}
