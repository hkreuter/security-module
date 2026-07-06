<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Settings;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettings;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\UnicodeString;

class TwoFAShopSettingsTest extends TestCase
{
    #[Test]
    public function isTwoFactorAuthEnabledReturnsTrueWhenEnabled(): void
    {
        $sut = $this->getSut(booleanSettings: [TwoFAShopSettings::ACTIVE => true]);

        $this->assertTrue($sut->isTwoFactorAuthEnabled());
    }

    #[Test]
    public function isTwoFactorAuthEnabledReturnsFalseWhenDisabled(): void
    {
        $sut = $this->getSut(booleanSettings: [TwoFAShopSettings::ACTIVE => false]);

        $this->assertFalse($sut->isTwoFactorAuthEnabled());
    }

    #[Test]
    public function getTwoFactorAuthTypeReturnsConfiguredType(): void
    {
        $type = uniqid();

        $sut = $this->getSut(stringSettings: [TwoFAShopSettings::TWO_FACTOR_TYPE => new UnicodeString($type)]);

        $this->assertSame($type, $sut->getTwoFactorAuthType());
    }

    #[Test]
    public function getApiChallengeLifetimeReturnsConfiguredSeconds(): void
    {
        $seconds = random_int(1, 3600);

        $sut = $this->getSut(integerSettings: [TwoFAShopSettings::API_CHALLENGE_LIFETIME => $seconds]);

        $this->assertSame($seconds, $sut->getApiChallengeLifetime());
    }

    #[Test]
    public function getOtpCodeLifetimeReturnsConfiguredSeconds(): void
    {
        $seconds = random_int(1, 3600);

        $sut = $this->getSut(integerSettings: [TwoFAShopSettings::OTP_CODE_LIFETIME => $seconds]);

        $this->assertSame($seconds, $sut->getOtpCodeLifetime());
    }

    #[Test]
    public function getEffectiveChallengeLifetimeClampsToOtpLifetimeWhenApiLifetimeIsLonger(): void
    {
        $otpLifetime = random_int(1, 300);
        $apiLifetime = $otpLifetime + random_int(1, 300);

        $sut = $this->getSut(integerSettings: [
            TwoFAShopSettings::API_CHALLENGE_LIFETIME => $apiLifetime,
            TwoFAShopSettings::OTP_CODE_LIFETIME => $otpLifetime,
        ]);

        $this->assertSame($otpLifetime, $sut->getEffectiveChallengeLifetime());
    }

    #[Test]
    public function getEffectiveChallengeLifetimeClampsToApiLifetimeWhenOtpLifetimeIsLonger(): void
    {
        $apiLifetime = random_int(1, 300);
        $otpLifetime = $apiLifetime + random_int(1, 300);

        $sut = $this->getSut(integerSettings: [
            TwoFAShopSettings::API_CHALLENGE_LIFETIME => $apiLifetime,
            TwoFAShopSettings::OTP_CODE_LIFETIME => $otpLifetime,
        ]);

        $this->assertSame($apiLifetime, $sut->getEffectiveChallengeLifetime());
    }

    #[Test]
    public function getVerificationUrlReturnsVerificationControllerUrl(): void
    {
        $sut = $this->getSut(shopHomeUrl: $homeUrl = uniqid());

        $this->assertSame($homeUrl . 'cl=oesm_twofactorauth', $sut->getVerificationUrl());
    }

    #[Test]
    public function getAccountUrlReturnsAccountControllerUrl(): void
    {
        $sut = $this->getSut(shopHomeUrl: $homeUrl = uniqid());

        $this->assertSame($homeUrl . 'cl=account', $sut->getAccountUrl());
    }

    #[Test]
    public function implementsInterface(): void
    {
        $this->assertInstanceOf(TwoFAShopSettingsInterface::class, $this->getSut());
    }

    /**
     * @param array<string, bool> $booleanSettings
     * @param array<string, UnicodeString> $stringSettings
     * @param array<string, int> $integerSettings
     */
    private function getSut(
        ?string $shopHomeUrl = null,
        array $booleanSettings = [],
        array $stringSettings = [],
        array $integerSettings = [],
    ): TwoFAShopSettings {
        $configStub = $this->createStub(Config::class);
        $configStub->method('getShopHomeUrl')->willReturn($shopHomeUrl ?? uniqid());

        $moduleSettingServiceStub = $this->createStub(ModuleSettingServiceInterface::class);
        $moduleSettingServiceStub->method('getBoolean')
            ->willReturnCallback(fn(string $name): bool => $booleanSettings[$name] ?? false);
        $moduleSettingServiceStub->method('getString')
            ->willReturnCallback(fn(string $name): UnicodeString => $stringSettings[$name] ?? new UnicodeString(''));
        $moduleSettingServiceStub->method('getInteger')
            ->willReturnCallback(fn(string $name): int => $integerSettings[$name] ?? 0);

        return new TwoFAShopSettings(
            config: $configStub,
            moduleSettingService: $moduleSettingServiceStub,
        );
    }
}
