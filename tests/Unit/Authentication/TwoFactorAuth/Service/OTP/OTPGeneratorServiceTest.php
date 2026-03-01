<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPGeneratorService;
use PHPUnit\Framework\TestCase;

class OTPGeneratorServiceTest extends TestCase
{
    public function testGenerateReturnsOTPWithCorrectUserId(): void
    {
        $sut = new OTPGeneratorService();
        $otp = $sut->generate('test-user-id', 6);

        $this->assertSame('test-user-id', $otp->userId);
    }

    public function testGenerateReturnsOTPWithCorrectCodeLength(): void
    {
        $sut = new OTPGeneratorService();
        $otp = $sut->generate('test-user-id', 6);

        $this->assertSame(6, strlen($otp->code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp->code);
    }

    public function testGenerateReturnsOTPWithEightDigitCode(): void
    {
        $sut = new OTPGeneratorService();
        $otp = $sut->generate('test-user-id', 8);

        $this->assertSame(8, strlen($otp->code));
        $this->assertMatchesRegularExpression('/^\d{8}$/', $otp->code);
    }

    public function testGenerateReturnsOTPWithZeroAttempts(): void
    {
        $sut = new OTPGeneratorService();
        $otp = $sut->generate('test-user-id', 6);

        $this->assertSame(0, $otp->attempts);
    }

    public function testGenerateReturnsOTPWithNullLastSentAt(): void
    {
        $sut = new OTPGeneratorService();
        $otp = $sut->generate('test-user-id', 6);

        $this->assertNull($otp->lastSentAt);
    }

    public function testGenerateReturnsOTPWithEmptySid(): void
    {
        $sut = new OTPGeneratorService();
        $otp = $sut->generate('test-user-id', 6);

        $this->assertSame('', $otp->sid);
    }

    public function testGenerateReturnsDifferentCodesOnSubsequentCalls(): void
    {
        $sut = new OTPGeneratorService();
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $sut->generate('test-user-id', 6)->code;
        }

        $this->assertGreaterThan(1, count(array_unique($codes)));
    }
}
