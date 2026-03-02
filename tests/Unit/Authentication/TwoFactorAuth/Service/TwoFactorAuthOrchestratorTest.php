<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPValidatorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFactorAuthOrchestrator;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFactorAuthOrchestratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(TwoFactorAuthOrchestrator::class)]
final class TwoFactorAuthOrchestratorTest extends TestCase
{
    private const USER_ID = 'test-user-id';
    private const EMAIL = 'user@example.com';
    private const SID = 'test-session-id';
    private const CONTEXT = 'frontend';
    private const RAW_CODE = '123456';
    private const MAX_ATTEMPTS = 5;

    private OTPServiceInterface&MockObject $otpService;
    private OTPValidatorServiceInterface&MockObject $otpValidator;
    private OTPAttemptServiceInterface&MockObject $attemptService;
    private EmailAdapterInterface&MockObject $emailAdapter;
    private OTPRepositoryInterface&MockObject $otpRepository;
    private ModuleSettingsServiceInterface&MockObject $moduleSettings;
    private LoggerInterface&MockObject $logger;
    private TwoFactorAuthOrchestrator $sut;

    protected function setUp(): void
    {
        $this->otpService = $this->createMock(OTPServiceInterface::class);
        $this->otpValidator = $this->createMock(OTPValidatorServiceInterface::class);
        $this->attemptService = $this->createMock(OTPAttemptServiceInterface::class);
        $this->emailAdapter = $this->createMock(EmailAdapterInterface::class);
        $this->otpRepository = $this->createMock(OTPRepositoryInterface::class);
        $this->moduleSettings = $this->createMock(ModuleSettingsServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->moduleSettings->method('getMaxAttempts')->willReturn(self::MAX_ATTEMPTS);

        $this->sut = new TwoFactorAuthOrchestrator(
            $this->otpService,
            $this->otpValidator,
            $this->attemptService,
            $this->emailAdapter,
            $this->otpRepository,
            $this->moduleSettings,
            $this->logger,
        );
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(TwoFactorAuthOrchestratorInterface::class, $this->sut);
    }

    // --- initiate ---

    public function testInitiateDeletesExistingOtpFirst(): void
    {
        $this->otpService->expects($this->once())
            ->method('delete')
            ->with(self::USER_ID);

        $this->otpService->method('getOTP')->willReturn($this->createNewOtp());

        $this->sut->initiate(self::USER_ID, self::EMAIL, self::SID, self::CONTEXT);
    }

    public function testInitiateCreatesNewOtp(): void
    {
        $this->otpService->expects($this->once())
            ->method('getOTP')
            ->with(self::USER_ID, self::SID, self::CONTEXT)
            ->willReturn($this->createNewOtp());

        $this->sut->initiate(self::USER_ID, self::EMAIL, self::SID, self::CONTEXT);
    }

    public function testInitiateSendsEmailWithRawCode(): void
    {
        $this->otpService->method('getOTP')->willReturn($this->createNewOtp());

        $this->emailAdapter->expects($this->once())
            ->method('send')
            ->with(self::EMAIL, self::RAW_CODE);

        $this->sut->initiate(self::USER_ID, self::EMAIL, self::SID, self::CONTEXT);
    }

    public function testInitiateLogs(): void
    {
        $this->otpService->method('getOTP')->willReturn($this->createNewOtp());

        $this->logger->expects($this->once())->method('info');

        $this->sut->initiate(self::USER_ID, self::EMAIL, self::SID, self::CONTEXT);
    }

    // --- verify: no OTP ---

    public function testVerifyReturnsFalseWhenNoOtpFound(): void
    {
        $this->otpRepository->method('find')->willReturn(null);

        $this->assertFalse($this->sut->verify(self::USER_ID, self::RAW_CODE));
    }

    // --- verify: blocked ---

    public function testVerifyReturnsFalseWhenBlocked(): void
    {
        $otp = $this->createStoredOtp(attempts: self::MAX_ATTEMPTS);
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(true);

        $this->assertFalse($this->sut->verify(self::USER_ID, self::RAW_CODE));
    }

    public function testVerifyDoesNotValidateCodeWhenBlocked(): void
    {
        $otp = $this->createStoredOtp(attempts: self::MAX_ATTEMPTS);
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(true);

        $this->otpValidator->expects($this->never())->method('codeMatches');

        $this->sut->verify(self::USER_ID, self::RAW_CODE);
    }

    // --- verify: expired ---

    public function testVerifyReturnsFalseWhenExpired(): void
    {
        $otp = $this->createStoredOtp();
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(false);
        $this->otpValidator->method('isExpired')->willReturn(true);

        $this->assertFalse($this->sut->verify(self::USER_ID, self::RAW_CODE));
    }

    // --- verify: success ---

    public function testVerifyReturnsTrueWhenCodeMatches(): void
    {
        $otp = $this->createStoredOtp();
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(false);
        $this->otpValidator->method('isExpired')->willReturn(false);
        $this->otpValidator->method('codeMatches')->willReturn(true);

        $this->assertTrue($this->sut->verify(self::USER_ID, self::RAW_CODE));
    }

    public function testVerifyDeletesOtpOnSuccess(): void
    {
        $otp = $this->createStoredOtp();
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(false);
        $this->otpValidator->method('isExpired')->willReturn(false);
        $this->otpValidator->method('codeMatches')->willReturn(true);

        $this->otpService->expects($this->once())
            ->method('delete')
            ->with(self::USER_ID);

        $this->sut->verify(self::USER_ID, self::RAW_CODE);
    }

    public function testVerifyLogsSuccess(): void
    {
        $otp = $this->createStoredOtp();
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(false);
        $this->otpValidator->method('isExpired')->willReturn(false);
        $this->otpValidator->method('codeMatches')->willReturn(true);

        $this->logger->expects($this->once())->method('info');

        $this->sut->verify(self::USER_ID, self::RAW_CODE);
    }

    // --- verify: wrong code ---

    public function testVerifyReturnsFalseWhenCodeDoesNotMatch(): void
    {
        $otp = $this->createStoredOtp();
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(false);
        $this->otpValidator->method('isExpired')->willReturn(false);
        $this->otpValidator->method('codeMatches')->willReturn(false);

        $this->assertFalse($this->sut->verify(self::USER_ID, '000000'));
    }

    public function testVerifyIncrementsAttemptsOnWrongCode(): void
    {
        $otp = $this->createStoredOtp();
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(false);
        $this->otpValidator->method('isExpired')->willReturn(false);
        $this->otpValidator->method('codeMatches')->willReturn(false);

        $this->attemptService->expects($this->once())
            ->method('increment')
            ->with(self::USER_ID);

        $this->sut->verify(self::USER_ID, '000000');
    }

    public function testVerifyLogsFailure(): void
    {
        $otp = $this->createStoredOtp();
        $this->otpRepository->method('find')->willReturn($otp);
        $this->otpValidator->method('maxAttemptsExceeded')->willReturn(false);
        $this->otpValidator->method('isExpired')->willReturn(false);
        $this->otpValidator->method('codeMatches')->willReturn(false);

        $this->logger->expects($this->once())->method('warning');

        $this->sut->verify(self::USER_ID, '000000');
    }

    // --- canRetry ---

    public function testCanRetryDelegatesToAttemptService(): void
    {
        $this->attemptService->expects($this->once())
            ->method('canRetry')
            ->with(self::USER_ID)
            ->willReturn(true);

        $this->assertTrue($this->sut->canRetry(self::USER_ID));
    }

    public function testCanRetryReturnsFalseWhenBlocked(): void
    {
        $this->attemptService->method('canRetry')->willReturn(false);

        $this->assertFalse($this->sut->canRetry(self::USER_ID));
    }

    // --- getRemainingAttempts ---

    public function testGetRemainingAttemptsReturnsMaxWhenNoOtpExists(): void
    {
        $this->otpRepository->method('find')->willReturn(null);

        $this->assertSame(self::MAX_ATTEMPTS, $this->sut->getRemainingAttempts(self::USER_ID));
    }

    public function testGetRemainingAttemptsComputesDifference(): void
    {
        $otp = $this->createStoredOtp(attempts: 2);
        $this->otpRepository->method('find')->willReturn($otp);

        $this->assertSame(3, $this->sut->getRemainingAttempts(self::USER_ID));
    }

    public function testGetRemainingAttemptsNeverReturnsNegative(): void
    {
        $otp = $this->createStoredOtp(attempts: 10);
        $this->otpRepository->method('find')->willReturn($otp);

        $this->assertSame(0, $this->sut->getRemainingAttempts(self::USER_ID));
    }

    // --- isBlocked ---

    public function testIsBlockedReturnsTrueWhenCannotRetry(): void
    {
        $this->attemptService->method('canRetry')->willReturn(false);

        $this->assertTrue($this->sut->isBlocked(self::USER_ID));
    }

    public function testIsBlockedReturnsFalseWhenCanRetry(): void
    {
        $this->attemptService->method('canRetry')->willReturn(true);

        $this->assertFalse($this->sut->isBlocked(self::USER_ID));
    }

    // --- helpers ---

    private function createNewOtp(): OTP
    {
        return new OTP(
            userId: self::USER_ID,
            code: self::RAW_CODE,
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: 0,
            lastSentAt: null,
            sid: self::SID,
            context: self::CONTEXT,
        );
    }

    private function createStoredOtp(int $attempts = 0): OTP
    {
        return new OTP(
            userId: self::USER_ID,
            code: hash('sha256', self::RAW_CODE . self::USER_ID),
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: $attempts,
            lastSentAt: null,
            sid: self::SID,
            context: self::CONTEXT,
        );
    }
}
