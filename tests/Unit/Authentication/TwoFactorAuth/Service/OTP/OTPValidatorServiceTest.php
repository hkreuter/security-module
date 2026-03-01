<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\MaxAttemptsExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\OTPExpiredException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPValidatorService;
use PHPUnit\Framework\TestCase;

class OTPValidatorServiceTest extends TestCase
{
    private const USER_ID = 'test-user-id';
    private const RAW_CODE = '123456';

    public function testValidateSucceedsWithCorrectCode(): void
    {
        $sut = $this->getSut(maxAttempts: 5);
        $otp = $this->createOTP(
            code: $this->hashCode(self::RAW_CODE, self::USER_ID),
            attempts: 0,
            expiresAt: new \DateTimeImmutable('+5 minutes')
        );

        $sut->validate($otp, self::RAW_CODE);
        $this->assertTrue(true);
    }

    public function testValidateThrowsMaxAttemptsExceeded(): void
    {
        $sut = $this->getSut(maxAttempts: 5);
        $otp = $this->createOTP(
            code: $this->hashCode(self::RAW_CODE, self::USER_ID),
            attempts: 5,
            expiresAt: new \DateTimeImmutable('+5 minutes')
        );

        $this->expectException(MaxAttemptsExceededException::class);
        $sut->validate($otp, self::RAW_CODE);
    }

    public function testValidateThrowsOTPExpired(): void
    {
        $sut = $this->getSut(maxAttempts: 5);
        $otp = $this->createOTP(
            code: $this->hashCode(self::RAW_CODE, self::USER_ID),
            attempts: 0,
            expiresAt: new \DateTimeImmutable('-1 second')
        );

        $this->expectException(OTPExpiredException::class);
        $sut->validate($otp, self::RAW_CODE);
    }

    public function testValidateThrowsInvalidCode(): void
    {
        $sut = $this->getSut(maxAttempts: 5);
        $otp = $this->createOTP(
            code: $this->hashCode(self::RAW_CODE, self::USER_ID),
            attempts: 0,
            expiresAt: new \DateTimeImmutable('+5 minutes')
        );

        $this->expectException(InvalidCodeException::class);
        $sut->validate($otp, '999999');
    }

    public function testValidateChecksMaxAttemptsBeforeExpiry(): void
    {
        $sut = $this->getSut(maxAttempts: 3);
        $otp = $this->createOTP(
            code: $this->hashCode(self::RAW_CODE, self::USER_ID),
            attempts: 3,
            expiresAt: new \DateTimeImmutable('-1 second')
        );

        $this->expectException(MaxAttemptsExceededException::class);
        $sut->validate($otp, self::RAW_CODE);
    }

    private function getSut(int $maxAttempts = 5): OTPValidatorService
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpMaxAttempts')->willReturn($maxAttempts);

        return new OTPValidatorService($settings);
    }

    private function createOTP(
        string $code,
        int $attempts,
        \DateTimeImmutable $expiresAt
    ): OTP {
        return new OTP(
            userId: self::USER_ID,
            code: $code,
            expiresAt: $expiresAt,
            attempts: $attempts,
            lastSentAt: null,
            sid: 'test-sid',
            context: 'frontend',
        );
    }

    private function hashCode(string $code, string $userId): string
    {
        return hash('sha256', $code . $userId);
    }
}
