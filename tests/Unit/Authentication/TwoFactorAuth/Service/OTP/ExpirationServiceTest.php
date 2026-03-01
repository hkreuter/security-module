<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\ExpirationService;
use PHPUnit\Framework\TestCase;

class ExpirationServiceTest extends TestCase
{
    public function testCalculateReturnsDateTimeInTheFuture(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpTtl')->willReturn(300);

        $sut = new ExpirationService($settings);
        $result = $sut->calculate();

        $this->assertGreaterThan(new \DateTimeImmutable(), $result);
    }

    public function testCalculateReturnsDateTimeWithCorrectOffset(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpTtl')->willReturn(300);

        $before = new \DateTimeImmutable();
        $sut = new ExpirationService($settings);
        $result = $sut->calculate();
        $after = new \DateTimeImmutable();

        $expectedMin = $before->modify('+300 seconds');
        $expectedMax = $after->modify('+300 seconds');

        $this->assertGreaterThanOrEqual($expectedMin, $result);
        $this->assertLessThanOrEqual($expectedMax, $result);
    }

    public function testCalculateUsesConfiguredTtl(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpTtl')->willReturn(600);

        $before = new \DateTimeImmutable();
        $sut = new ExpirationService($settings);
        $result = $sut->calculate();

        $expectedMin = $before->modify('+600 seconds');

        $this->assertGreaterThanOrEqual($expectedMin, $result);
    }
}
