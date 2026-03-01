<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\ExpirationServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPGeneratorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPService;
use PHPUnit\Framework\TestCase;

class OTPServiceTest extends TestCase
{
    private const USER_ID = 'test-user-id';

    public function testGetOTPReturnsExistingNonExpiredOTP(): void
    {
        $existingOtp = $this->createOTP(expiresAt: new \DateTimeImmutable('+5 minutes'));

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($existingOtp);

        $sut = $this->getSut(repository: $repository);

        $result = $sut->getOTP(self::USER_ID);
        $this->assertSame($existingOtp, $result);
    }

    public function testGetOTPCreatesNewWhenNoneExists(): void
    {
        $newOtp = $this->createOTP(expiresAt: new \DateTimeImmutable('+5 minutes'));

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('save');

        $generator = $this->createMock(OTPGeneratorServiceInterface::class);
        $generator->method('generate')
            ->willReturn($newOtp);

        $sut = $this->getSut(repository: $repository, generator: $generator);

        $result = $sut->getOTP(self::USER_ID);
        $this->assertSame(self::USER_ID, $result->userId);
    }

    public function testGetOTPDeletesExpiredAndCreatesNew(): void
    {
        $expiredOtp = $this->createOTP(expiresAt: new \DateTimeImmutable('-1 second'));
        $newOtp = $this->createOTP(expiresAt: new \DateTimeImmutable('+5 minutes'));

        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->method('find')
            ->with(self::USER_ID)
            ->willReturn($expiredOtp);
        $repository->expects($this->once())
            ->method('delete')
            ->with(self::USER_ID);
        $repository->expects($this->once())
            ->method('save');

        $generator = $this->createMock(OTPGeneratorServiceInterface::class);
        $generator->method('generate')
            ->willReturn($newOtp);

        $sut = $this->getSut(repository: $repository, generator: $generator);

        $sut->getOTP(self::USER_ID);
    }

    public function testDeleteCallsRepository(): void
    {
        $repository = $this->createMock(OTPRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('delete')
            ->with(self::USER_ID);

        $sut = $this->getSut(repository: $repository);

        $sut->delete(self::USER_ID);
    }

    private function getSut(
        ?OTPRepositoryInterface $repository = null,
        ?OTPGeneratorServiceInterface $generator = null,
        ?ExpirationServiceInterface $expiration = null,
        ?ModuleSettingsServiceInterface $settings = null,
    ): OTPService {
        return new OTPService(
            repository: $repository ?? $this->createStub(OTPRepositoryInterface::class),
            generator: $generator ?? $this->createStub(OTPGeneratorServiceInterface::class),
            expirationService: $expiration ?? $this->createExpirationStub(),
            settings: $settings ?? $this->createSettingsStub(),
        );
    }

    private function createExpirationStub(): ExpirationServiceInterface
    {
        $stub = $this->createStub(ExpirationServiceInterface::class);
        $stub->method('calculate')->willReturn(new \DateTimeImmutable('+5 minutes'));
        return $stub;
    }

    private function createSettingsStub(): ModuleSettingsServiceInterface
    {
        $stub = $this->createStub(ModuleSettingsServiceInterface::class);
        $stub->method('getOtpLength')->willReturn(6);
        return $stub;
    }

    private function createOTP(\DateTimeImmutable $expiresAt): OTP
    {
        return new OTP(
            userId: self::USER_ID,
            code: '123456',
            expiresAt: $expiresAt,
            attempts: 0,
            lastSentAt: null,
            sid: 'test-sid',
            context: 'frontend',
        );
    }
}
