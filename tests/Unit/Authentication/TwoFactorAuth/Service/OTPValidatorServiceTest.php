<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPValidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OTPValidatorService::class)]
final class OTPValidatorServiceTest extends TestCase
{
    private const USER_ID = 'test-user-id';
    private const RAW_CODE = '123456';

    public function testCodeMatchesReturnsTrueForValidCode(): void
    {
        $hash = hash('sha256', self::RAW_CODE . self::USER_ID);
        $otp = $this->createOTP(code: $hash);

        $sut = new OTPValidatorService($this->createSettings());

        $this->assertTrue($sut->codeMatches($otp, self::RAW_CODE));
    }

    public function testCodeMatchesReturnsFalseForInvalidCode(): void
    {
        $hash = hash('sha256', self::RAW_CODE . self::USER_ID);
        $otp = $this->createOTP(code: $hash);

        $sut = new OTPValidatorService($this->createSettings());

        $this->assertFalse($sut->codeMatches($otp, '000000'));
    }

    public function testIsExpiredReturnsFalseWhenCodeIsStillValid(): void
    {
        $otp = $this->createOTP(
            expiresAt: new \DateTimeImmutable('+5 minutes'),
        );

        $sut = new OTPValidatorService($this->createSettings());

        $this->assertFalse($sut->isExpired($otp));
    }

    public function testIsExpiredReturnsTrueWhenCodeHasExpired(): void
    {
        $otp = $this->createOTP(
            expiresAt: new \DateTimeImmutable('-1 second'),
        );

        $sut = new OTPValidatorService($this->createSettings());

        $this->assertTrue($sut->isExpired($otp));
    }

    public function testMaxAttemptsExceededReturnsFalseWhenUnderLimit(): void
    {
        $otp = $this->createOTP(attempts: 2);

        $sut = new OTPValidatorService($this->createSettings(maxAttempts: 3));

        $this->assertFalse($sut->maxAttemptsExceeded($otp));
    }

    public function testMaxAttemptsExceededReturnsTrueWhenAtLimit(): void
    {
        $otp = $this->createOTP(attempts: 3);

        $sut = new OTPValidatorService($this->createSettings(maxAttempts: 3));

        $this->assertTrue($sut->maxAttemptsExceeded($otp));
    }

    public function testMaxAttemptsExceededReturnsTrueWhenOverLimit(): void
    {
        $otp = $this->createOTP(attempts: 5);

        $sut = new OTPValidatorService($this->createSettings(maxAttempts: 3));

        $this->assertTrue($sut->maxAttemptsExceeded($otp));
    }

    public function testCanResendReturnsTrueWhenCooldownHasPassed(): void
    {
        $otp = $this->createOTP(
            lastSentAt: new \DateTimeImmutable('-120 seconds'),
        );

        $sut = new OTPValidatorService($this->createSettings(cooldown: 60));

        $this->assertTrue($sut->canResend($otp));
    }

    public function testCanResendReturnsFalseWhenWithinCooldown(): void
    {
        $otp = $this->createOTP(
            lastSentAt: new \DateTimeImmutable('-10 seconds'),
        );

        $sut = new OTPValidatorService($this->createSettings(cooldown: 60));

        $this->assertFalse($sut->canResend($otp));
    }

    public function testCanResendReturnsTrueWhenNeverSent(): void
    {
        $otp = $this->createOTP(lastSentAt: null);

        $sut = new OTPValidatorService($this->createSettings(cooldown: 60));

        $this->assertTrue($sut->canResend($otp));
    }

    private function createOTP(
        string $code = '',
        ?\DateTimeImmutable $expiresAt = null,
        int $attempts = 0,
        ?\DateTimeImmutable $lastSentAt = null,
    ): OTP {
        return new OTP(
            userId: self::USER_ID,
            code: $code,
            expiresAt: $expiresAt ?? new \DateTimeImmutable('+5 minutes'),
            attempts: $attempts,
            lastSentAt: $lastSentAt,
            sid: 'test-sid',
            context: 'frontend',
        );
    }

    private function createSettings(
        int $maxAttempts = 3,
        int $cooldown = 60,
    ): ModuleSettingsServiceInterface {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getMaxAttempts')->willReturn($maxAttempts);
        $settings->method('getCooldown')->willReturn($cooldown);

        return $settings;
    }
}
