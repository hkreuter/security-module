<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepository;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ExpirationService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPGeneratorService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPValidatorService;

final class OTPRoundTripTest extends IntegrationTestCase
{
    private const TABLE = 'oe_security_2fa';
    private const SID = 'test-session-id-roundtrip-00001';
    private const CONTEXT = 'frontend';

    private string $testUserId = '';
    private OTPRepositoryInterface $repository;
    private OTPService $otpService;
    private OTPValidatorService $validatorService;
    private OTPAttemptService $attemptService;
    private ModuleSettingsServiceInterface $settings;

    public function setUp(): void
    {
        parent::setUp();
        $this->cleanUpTable();
        $this->testUserId = $this->getExistingUserId();
        $this->buildServices();
    }

    public function tearDown(): void
    {
        $this->cleanUpTable();
        parent::tearDown();
    }

    public function testGenerateStoreRetrieveValidate(): void
    {
        $otp = $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);
        $rawCode = $otp->getCode();

        $stored = $this->repository->find($this->testUserId);

        $this->assertNotNull($stored);
        $this->assertTrue(
            $this->validatorService->codeMatches($stored, $rawCode),
            'Stored OTP must validate against the raw code that was generated',
        );
    }

    public function testStoredCodeIsHashed(): void
    {
        $otp = $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);
        $rawCode = $otp->getCode();

        $stored = $this->repository->find($this->testUserId);

        $this->assertNotNull($stored);
        $this->assertNotSame($rawCode, $stored->getCode(), 'DB must store hash, not raw code');
    }

    public function testWrongCodeRejected(): void
    {
        $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);
        $stored = $this->repository->find($this->testUserId);

        $this->assertNotNull($stored);
        $this->assertFalse(
            $this->validatorService->codeMatches($stored, '000000'),
            'Wrong code must not validate',
        );
    }

    public function testOtpIsNotExpiredImmediately(): void
    {
        $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);
        $stored = $this->repository->find($this->testUserId);

        $this->assertNotNull($stored);
        $this->assertFalse($this->validatorService->isExpired($stored));
    }

    public function testGetOTPReturnsExistingWhenStillValid(): void
    {
        $otp1 = $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);
        $otp2 = $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);

        $this->assertSame(
            $otp1->getExpiresAt()->format('Y-m-d H:i:s'),
            $otp2->getExpiresAt()->format('Y-m-d H:i:s'),
            'Second call must return the same OTP, not create a new one',
        );
    }

    public function testAttemptTrackingCanRetryUnderLimit(): void
    {
        $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);

        $this->assertTrue($this->attemptService->canRetry($this->testUserId));

        $this->attemptService->increment($this->testUserId);
        $this->attemptService->increment($this->testUserId);

        $this->assertTrue(
            $this->attemptService->canRetry($this->testUserId),
            'User must be able to retry when under max attempts',
        );
    }

    public function testAttemptTrackingBlocksAfterMaxAttempts(): void
    {
        $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);

        $maxAttempts = $this->settings->getMaxAttempts();
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->attemptService->increment($this->testUserId);
        }

        $this->assertFalse(
            $this->attemptService->canRetry($this->testUserId),
            'User must be blocked after max attempts exhausted',
        );
    }

    public function testDeleteCleansUp(): void
    {
        $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);
        $this->assertNotNull($this->repository->find($this->testUserId));

        $this->otpService->delete($this->testUserId);

        $this->assertNull($this->repository->find($this->testUserId));
    }

    public function testDeleteResetsAttemptBlocking(): void
    {
        $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);

        $maxAttempts = $this->settings->getMaxAttempts();
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->attemptService->increment($this->testUserId);
        }
        $this->assertFalse($this->attemptService->canRetry($this->testUserId));

        $this->otpService->delete($this->testUserId);

        $this->assertTrue(
            $this->attemptService->canRetry($this->testUserId),
            'After delete, user must be able to retry',
        );
    }

    public function testFullRoundTrip(): void
    {
        // 1. Generate OTP
        $otp = $this->otpService->getOTP($this->testUserId, self::SID, self::CONTEXT);
        $rawCode = $otp->getCode();

        // 2. Retrieve from DB
        $stored = $this->repository->find($this->testUserId);
        $this->assertNotNull($stored);

        // 3. Validate correct code
        $this->assertTrue($this->validatorService->codeMatches($stored, $rawCode));
        $this->assertFalse($this->validatorService->isExpired($stored));
        $this->assertFalse($this->validatorService->maxAttemptsExceeded($stored));

        // 4. Simulate failed attempt
        $this->attemptService->increment($this->testUserId);
        $this->assertTrue($this->attemptService->canRetry($this->testUserId));

        // 5. Re-fetch and validate again (code still works)
        $stored = $this->repository->find($this->testUserId);
        $this->assertNotNull($stored);
        $this->assertTrue($this->validatorService->codeMatches($stored, $rawCode));
        $this->assertSame(1, $stored->getAttempts());

        // 6. Clean up
        $this->otpService->delete($this->testUserId);
        $this->assertNull($this->repository->find($this->testUserId));
    }

    private function buildServices(): void
    {
        $this->settings = $this->createSettingsStub();
        $this->repository = new OTPRepository(
            $this->get(QueryBuilderFactoryInterface::class),
        );

        $this->otpService = new OTPService(
            $this->repository,
            new OTPGeneratorService($this->settings),
            new ExpirationService($this->settings),
        );
        $this->validatorService = new OTPValidatorService($this->settings);
        $this->attemptService = new OTPAttemptService($this->repository, $this->settings);
    }

    private function createSettingsStub(): ModuleSettingsServiceInterface
    {
        $stub = $this->createMock(ModuleSettingsServiceInterface::class);
        $stub->method('isTwoFactorAuthEnabled')->willReturn(true);
        $stub->method('getOtpLength')->willReturn(6);
        $stub->method('getOtpLifetime')->willReturn(300);
        $stub->method('getMaxAttempts')->willReturn(5);
        $stub->method('getCooldown')->willReturn(60);

        return $stub;
    }

    private function getExistingUserId(): string
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $result = $queryBuilder
            ->select('OXID')
            ->from('oxuser')
            ->setMaxResults(1)
            ->execute();

        if (is_object($result)) {
            $userId = $result->fetchOne();
            if ($userId !== false) {
                return (string) $userId;
            }
        }

        throw new \RuntimeException('No user found in oxuser table for integration test');
    }

    private function cleanUpTable(): void
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->delete(self::TABLE)->execute();
    }
}
