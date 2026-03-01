<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptService;
use PHPUnit\Framework\TestCase;

class OTPAttemptServiceTest extends TestCase
{
    private const USER_ID = 'test-user-id';

    public function testIncrementCallsRepositoryIncrementAttempts(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('incrementAttempts')
            ->with(self::USER_ID);

        $sut = new OTPAttemptService(
            $repository,
            $this->createSettingsMock(5, 300)
        );

        $sut->increment(self::USER_ID);
    }

    public function testCanRetryReturnsTrueWhenBelowMaxAttempts(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($this->createOTP(attempts: 2));

        $sut = new OTPAttemptService(
            $repository,
            $this->createSettingsMock(5, 300)
        );

        $this->assertTrue($sut->canRetry(self::USER_ID));
    }

    public function testCanRetryReturnsFalseWhenAtMaxAttempts(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($this->createOTP(attempts: 5));

        $sut = new OTPAttemptService(
            $repository,
            $this->createSettingsMock(5, 300)
        );

        $this->assertFalse($sut->canRetry(self::USER_ID));
    }

    public function testCanRetryReturnsTrueWhenNoOTPExists(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn(null);

        $sut = new OTPAttemptService(
            $repository,
            $this->createSettingsMock(5, 300)
        );

        $this->assertTrue($sut->canRetry(self::USER_ID));
    }

    private function createSettingsMock(int $maxAttempts, int $blockDuration): ModuleSettingsServiceInterface
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpMaxAttempts')->willReturn($maxAttempts);
        $settings->method('getOtpBlockDuration')->willReturn($blockDuration);
        return $settings;
    }

    private function createOTP(int $attempts): OTP
    {
        return new OTP(
            userId: self::USER_ID,
            code: 'hashed-code',
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: $attempts,
            lastSentAt: null,
            sid: 'test-sid',
            context: 'frontend',
        );
    }
}
