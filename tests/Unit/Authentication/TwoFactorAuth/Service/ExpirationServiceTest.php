<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ExpirationService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExpirationService::class)]
final class ExpirationServiceTest extends TestCase
{
    public function testCalculateReturnsDateTimeImmutable(): void
    {
        $sut = new ExpirationService($this->createSettings(300));

        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->calculate());
    }

    public function testCalculateReturnsFutureTimestamp(): void
    {
        $sut = new ExpirationService($this->createSettings(300));

        $this->assertGreaterThan(new \DateTimeImmutable(), $sut->calculate());
    }

    #[DataProvider('ttlProvider')]
    public function testCalculateAddsConfiguredTtl(int $ttl): void
    {
        $before = new \DateTimeImmutable();

        $sut = new ExpirationService($this->createSettings($ttl));
        $result = $sut->calculate();

        $after = new \DateTimeImmutable();

        $expectedMin = $before->modify("+{$ttl} seconds");
        $expectedMax = $after->modify("+{$ttl} seconds");

        $this->assertGreaterThanOrEqual($expectedMin, $result);
        $this->assertLessThanOrEqual($expectedMax, $result);
    }

    public static function ttlProvider(): \Generator
    {
        yield '60 seconds' => [60];
        yield '300 seconds' => [300];
        yield '600 seconds' => [600];
    }

    private function createSettings(int $ttl): ModuleSettingsServiceInterface
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpLifetime')->willReturn($ttl);

        return $settings;
    }
}
