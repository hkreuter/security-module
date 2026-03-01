<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\MaxAttemptsExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPValidatorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFactorAuthOrchestrator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TwoFactorAuthOrchestratorTest extends TestCase
{
    private const USER_ID = 'test-user-id';
    private const USER_EMAIL = 'test@example.com';

    public function testInitiateCreatesOTP(): void
    {
        $otp = $this->createOTP(code: '123456');

        $otpService = $this->createMock(OTPServiceInterface::class);
        $otpService->expects($this->once())
            ->method('getOTP')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $sut = $this->getSut(otpService: $otpService);

        $sut->initiate(self::USER_ID, 'sid123', 'frontend');
    }

    public function testSendCodeGetsOTPAndSendsEmail(): void
    {
        $otp = $this->createOTP(code: '123456');

        $otpService = $this->createMock(OTPServiceInterface::class);
        $otpService->expects($this->once())
            ->method('getOTP')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $emailAdapter = $this->createMock(EmailAdapterInterface::class);
        $emailAdapter->expects($this->once())
            ->method('send')
            ->with(self::USER_EMAIL, '123456');

        $sut = $this->getSut(
            otpService: $otpService,
            emailAdapter: $emailAdapter,
        );

        $sut->sendCode(self::USER_ID, self::USER_EMAIL);
    }

    public function testVerifyReturnsTrueOnValidCode(): void
    {
        $otp = $this->createOTP(code: 'hashed', attempts: 0);

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $validator = $this->createMock(OTPValidatorServiceInterface::class);

        $otpService = $this->createMock(OTPServiceInterface::class);
        $otpService->expects($this->once())
            ->method('delete')
            ->with(self::USER_ID);

        $sut = $this->getSut(
            otpService: $otpService,
            validator: $validator,
            repository: $repository,
        );

        $this->assertTrue($sut->verify(self::USER_ID, '123456'));
    }

    public function testVerifyReturnsFalseOnInvalidCode(): void
    {
        $otp = $this->createOTP(code: 'hashed', attempts: 0);

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $validator = $this->createMock(OTPValidatorServiceInterface::class);
        $validator->method('validate')
            ->willThrowException(new InvalidCodeException());

        $attemptService = $this->createMock(OTPAttemptServiceInterface::class);
        $attemptService->expects($this->once())
            ->method('increment')
            ->with(self::USER_ID);

        $sut = $this->getSut(
            validator: $validator,
            attemptService: $attemptService,
            repository: $repository,
        );

        $this->assertFalse($sut->verify(self::USER_ID, 'wrong'));
    }

    public function testVerifyReturnsFalseOnMaxAttempts(): void
    {
        $otp = $this->createOTP(code: 'hashed', attempts: 5);

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $validator = $this->createMock(OTPValidatorServiceInterface::class);
        $validator->method('validate')
            ->willThrowException(new MaxAttemptsExceededException());

        $sut = $this->getSut(
            validator: $validator,
            repository: $repository,
        );

        $this->assertFalse($sut->verify(self::USER_ID, '123456'));
    }

    public function testVerifyReturnsFalseWhenNoOTPExists(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn(null);

        $sut = $this->getSut(repository: $repository);

        $this->assertFalse($sut->verify(self::USER_ID, '123456'));
    }

    public function testCanRetryDelegatesToAttemptService(): void
    {
        $attemptService = $this->createMock(OTPAttemptServiceInterface::class);
        $attemptService->method('canRetry')
            ->with(self::USER_ID)
            ->willReturn(true);

        $sut = $this->getSut(attemptService: $attemptService);

        $this->assertTrue($sut->canRetry(self::USER_ID));
    }

    public function testGetRemainingAttempts(): void
    {
        $otp = $this->createOTP(attempts: 2);

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpMaxAttempts')->willReturn(5);

        $sut = $this->getSut(repository: $repository, settings: $settings);

        $this->assertSame(3, $sut->getRemainingAttempts(self::USER_ID));
    }

    public function testGetRemainingAttemptsReturnsMaxWhenNoOTP(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn(null);

        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpMaxAttempts')->willReturn(5);

        $sut = $this->getSut(repository: $repository, settings: $settings);

        $this->assertSame(5, $sut->getRemainingAttempts(self::USER_ID));
    }

    public function testIsBlockedReturnsTrueWhenMaxAttemptsReached(): void
    {
        $otp = $this->createOTP(attempts: 5);

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpMaxAttempts')->willReturn(5);

        $sut = $this->getSut(repository: $repository, settings: $settings);

        $this->assertTrue($sut->isBlocked(self::USER_ID));
    }

    public function testIsBlockedReturnsFalseWhenBelowMax(): void
    {
        $otp = $this->createOTP(attempts: 2);

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($otp);

        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('getOtpMaxAttempts')->willReturn(5);

        $sut = $this->getSut(repository: $repository, settings: $settings);

        $this->assertFalse($sut->isBlocked(self::USER_ID));
    }

    private function getSut(
        ?OTPServiceInterface $otpService = null,
        ?OTPValidatorServiceInterface $validator = null,
        ?OTPAttemptServiceInterface $attemptService = null,
        ?EmailAdapterInterface $emailAdapter = null,
        ?OTPRepositoryInterface $repository = null,
        ?ModuleSettingsServiceInterface $settings = null,
        ?LoggerInterface $logger = null,
    ): TwoFactorAuthOrchestrator {
        return new TwoFactorAuthOrchestrator(
            otpService: $otpService ?? $this->createStub(OTPServiceInterface::class),
            validator: $validator ?? $this->createStub(OTPValidatorServiceInterface::class),
            attemptService: $attemptService ?? $this->createStub(OTPAttemptServiceInterface::class),
            emailAdapter: $emailAdapter ?? $this->createStub(EmailAdapterInterface::class),
            repository: $repository ?? $this->createStub(OTPRepositoryInterface::class),
            settings: $settings ?? $this->createSettingsStub(),
            logger: $logger ?? $this->createStub(LoggerInterface::class),
        );
    }

    private function createSettingsStub(): ModuleSettingsServiceInterface
    {
        $stub = $this->createStub(ModuleSettingsServiceInterface::class);
        $stub->method('getOtpMaxAttempts')->willReturn(5);
        return $stub;
    }

    private function createOTP(
        string $code = 'hashed',
        int $attempts = 0,
    ): OTP {
        return new OTP(
            userId: self::USER_ID,
            code: $code,
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: $attempts,
            lastSentAt: null,
            sid: 'test-sid',
            context: 'frontend',
        );
    }
}
