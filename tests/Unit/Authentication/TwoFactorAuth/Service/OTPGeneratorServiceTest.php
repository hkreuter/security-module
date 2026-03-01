<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPGeneratorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OTPGeneratorService::class)]
final class OTPGeneratorServiceTest extends TestCase
{
    #[DataProvider('lengthDataProvider')]
    public function testGenerateReturnsCodeOfConfiguredLength(int $length): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpLength')->willReturn($length);

        $sut = new OTPGeneratorService($settings);

        $code = $sut->generate();

        $this->assertSame($length, strlen($code));
    }

    public static function lengthDataProvider(): array
    {
        return [
            '4 digits' => [4],
            '6 digits' => [6],
            '8 digits' => [8],
        ];
    }

    public function testGenerateReturnsOnlyDigits(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpLength')->willReturn(6);

        $sut = new OTPGeneratorService($settings);

        $code = $sut->generate();

        $this->assertMatchesRegularExpression('/^\d+$/', $code);
    }

    public function testGenerateReturnsDifferentCodes(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpLength')->willReturn(6);

        $sut = new OTPGeneratorService($settings);

        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $sut->generate();
        }

        $unique = array_unique($codes);
        $this->assertGreaterThan(1, count($unique));
    }

    public function testGeneratePreservesLeadingZeros(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpLength')->willReturn(6);

        $sut = new OTPGeneratorService($settings);

        // Generate many codes; length must always match even if leading zeros
        for ($i = 0; $i < 50; $i++) {
            $code = $sut->generate();
            $this->assertSame(6, strlen($code), "Code '$code' has wrong length");
        }
    }
}
