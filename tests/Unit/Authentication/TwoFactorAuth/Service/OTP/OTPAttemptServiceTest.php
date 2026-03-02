<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OTPAttemptService::class)]
final class OTPAttemptServiceTest extends TestCase
{
    private const USER_ID = 'test-user-id';

    public function testImplementsInterface(): void
    {
        $sut = new OTPAttemptService(
            $this->createMock(OTPRepositoryInterface::class),
            $this->createMock(ModuleSettingsServiceInterface::class),
        );

        $this->assertInstanceOf(OTPAttemptServiceInterface::class, $sut);
    }

    public function testIncrementDelegatesToRepository(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('incrementAttempts')
            ->with(self::USER_ID);

        $sut = new OTPAttemptService(
            $repository,
            $this->createMock(ModuleSettingsServiceInterface::class),
        );

        $sut->increment(self::USER_ID);
    }

    public function testCanRetryReturnsTrueWhenNoOtpRecordExists(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')->with(self::USER_ID)->willReturn(null);

        $sut = new OTPAttemptService(
            $repository,
            $this->createMock(ModuleSettingsServiceInterface::class),
        );

        $this->assertTrue($sut->canRetry(self::USER_ID));
    }

    #[DataProvider('attemptsUnderLimitProvider')]
    public function testCanRetryReturnsTrueWhenAttemptsUnderLimit(
        int $attempts,
        int $maxAttempts,
    ): void {
        $sut = new OTPAttemptService(
            $this->createRepositoryWithOtp($attempts),
            $this->createSettings($maxAttempts),
        );

        $this->assertTrue($sut->canRetry(self::USER_ID));
    }

    public static function attemptsUnderLimitProvider(): \Generator
    {
        yield '0 of 5' => [0, 5];
        yield '1 of 5' => [1, 5];
        yield '4 of 5' => [4, 5];
        yield '0 of 3' => [0, 3];
        yield '2 of 3' => [2, 3];
    }

    #[DataProvider('attemptsAtOrOverLimitProvider')]
    public function testCanRetryReturnsFalseWhenAttemptsExhausted(
        int $attempts,
        int $maxAttempts,
    ): void {
        $sut = new OTPAttemptService(
            $this->createRepositoryWithOtp($attempts),
            $this->createSettings($maxAttempts),
        );

        $this->assertFalse($sut->canRetry(self::USER_ID));
    }

    public static function attemptsAtOrOverLimitProvider(): \Generator
    {
        yield 'exactly at limit: 5 of 5' => [5, 5];
        yield 'over limit: 6 of 5' => [6, 5];
        yield 'exactly at limit: 3 of 3' => [3, 3];
        yield 'over limit: 10 of 3' => [10, 3];
    }

    private function createRepositoryWithOtp(int $attempts): OTPRepositoryInterface
    {
        $otp = new OTP(
            userId: self::USER_ID,
            code: 'hashed-code',
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: $attempts,
            lastSentAt: null,
            sid: 'session-id',
            context: 'frontend',
        );

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')->with(self::USER_ID)->willReturn($otp);

        return $repository;
    }

    private function createSettings(int $maxAttempts): ModuleSettingsServiceInterface
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getMaxAttempts')->willReturn($maxAttempts);

        return $settings;
    }
}
