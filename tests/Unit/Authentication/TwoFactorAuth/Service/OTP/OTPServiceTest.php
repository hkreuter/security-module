<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ExpirationServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPGeneratorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(OTPService::class)]
final class OTPServiceTest extends TestCase
{
    private const USER_ID = 'test-user-id';
    private const SID = 'test-session-id';
    private const CONTEXT = 'frontend';
    private const RAW_CODE = '123456';

    private OTPRepositoryInterface&MockObject $repository;
    private OTPGeneratorServiceInterface&MockObject $generator;
    private ExpirationServiceInterface&MockObject $expirationService;
    private OTPService $sut;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(OTPRepositoryInterface::class);
        $this->generator = $this->createMock(OTPGeneratorServiceInterface::class);
        $this->expirationService = $this->createMock(ExpirationServiceInterface::class);

        $this->sut = new OTPService(
            $this->repository,
            $this->generator,
            $this->expirationService,
        );
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(OTPServiceInterface::class, $this->sut);
    }

    // --- getOTP: existing valid OTP ---

    public function testGetOTPReturnsExistingWhenNotExpired(): void
    {
        $existing = $this->createOtp(
            expiresAt: new \DateTimeImmutable('+5 minutes'),
        );
        $this->repository->method('find')->with(self::USER_ID)->willReturn($existing);

        $this->generator->expects($this->never())->method('generate');

        $result = $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);

        $this->assertSame($existing, $result);
    }

    public function testGetOTPDoesNotDeleteExistingValidOtp(): void
    {
        $existing = $this->createOtp(
            expiresAt: new \DateTimeImmutable('+5 minutes'),
        );
        $this->repository->method('find')->willReturn($existing);

        $this->repository->expects($this->never())->method('delete');

        $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);
    }

    // --- getOTP: no existing OTP ---

    public function testGetOTPCreatesNewWhenNoExistingRecord(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->generator->method('generate')->willReturn(self::RAW_CODE);
        $this->expirationService->method('calculate')
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $this->repository->expects($this->once())->method('save');

        $result = $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);

        $this->assertSame(self::USER_ID, $result->getUserId());
    }

    public function testGetOTPUsesGeneratorForNewCode(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->expirationService->method('calculate')
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $this->generator->expects($this->once())->method('generate')
            ->willReturn(self::RAW_CODE);

        $result = $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);

        $this->assertSame(self::RAW_CODE, $result->getCode());
    }

    public function testGetOTPUsesExpirationServiceForExpiry(): void
    {
        $expectedExpiry = new \DateTimeImmutable('+10 minutes');
        $this->repository->method('find')->willReturn(null);
        $this->generator->method('generate')->willReturn(self::RAW_CODE);

        $this->expirationService->expects($this->once())->method('calculate')
            ->willReturn($expectedExpiry);

        $result = $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);

        $this->assertEquals($expectedExpiry, $result->getExpiresAt());
    }

    public function testGetOTPSavesNewOtpWithRawCode(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->generator->method('generate')->willReturn(self::RAW_CODE);
        $this->expirationService->method('calculate')
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $this->repository->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(OTP::class),
                self::RAW_CODE,
            );

        $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);
    }

    public function testGetOTPNewOtpHasZeroAttempts(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->generator->method('generate')->willReturn(self::RAW_CODE);
        $this->expirationService->method('calculate')
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $result = $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);

        $this->assertSame(0, $result->getAttempts());
    }

    public function testGetOTPNewOtpHasCorrectSidAndContext(): void
    {
        $this->repository->method('find')->willReturn(null);
        $this->generator->method('generate')->willReturn(self::RAW_CODE);
        $this->expirationService->method('calculate')
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $result = $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);

        $this->assertSame(self::SID, $result->getSid());
        $this->assertSame(self::CONTEXT, $result->getContext());
    }

    // --- getOTP: expired OTP ---

    public function testGetOTPDeletesExpiredOtpBeforeCreatingNew(): void
    {
        $expired = $this->createOtp(
            expiresAt: new \DateTimeImmutable('-1 second'),
        );
        $this->repository->method('find')->willReturn($expired);
        $this->generator->method('generate')->willReturn(self::RAW_CODE);
        $this->expirationService->method('calculate')
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $this->repository->expects($this->once())
            ->method('delete')
            ->with(self::USER_ID);

        $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);
    }

    public function testGetOTPCreatesNewOtpWhenExistingIsExpired(): void
    {
        $expired = $this->createOtp(
            expiresAt: new \DateTimeImmutable('-1 second'),
        );
        $this->repository->method('find')->willReturn($expired);
        $this->generator->method('generate')->willReturn(self::RAW_CODE);
        $this->expirationService->method('calculate')
            ->willReturn(new \DateTimeImmutable('+5 minutes'));

        $result = $this->sut->getOTP(self::USER_ID, self::SID, self::CONTEXT);

        $this->assertSame(self::RAW_CODE, $result->getCode());
        $this->assertNotSame($expired, $result);
    }

    // --- delete ---

    public function testDeleteDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(self::USER_ID);

        $this->sut->delete(self::USER_ID);
    }

    // --- helpers ---

    private function createOtp(\DateTimeImmutable $expiresAt): OTP
    {
        return new OTP(
            userId: self::USER_ID,
            code: 'hashed-code',
            expiresAt: $expiresAt,
            attempts: 0,
            lastSentAt: null,
            sid: self::SID,
            context: self::CONTEXT,
        );
    }
}
