<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Authentication\TwoFactorAuth\Infrastructure\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepository;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(OTPRepository::class)]
final class OTPRepositoryTest extends IntegrationTestCase
{
    private const TABLE = 'oe_security_2fa';
    private const TEST_SID = 'test-session-id-0000000000000001';
    private const TEST_RAW_CODE = '123456';

    private string $testUserId = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->cleanUpTable();
        $this->testUserId = $this->getExistingUserId();
    }

    public function tearDown(): void
    {
        $this->cleanUpTable();
        parent::tearDown();
    }

    public function testSaveAndFindByUserId(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();

        $sut->save($otp, self::TEST_RAW_CODE);

        $found = $sut->find($this->testUserId);
        $this->assertNotNull($found);
        $this->assertSame($this->testUserId, $found->getUserId());
        $this->assertSame(self::TEST_SID, $found->getSid());
        $this->assertSame('frontend', $found->getContext());
        $this->assertSame(0, $found->getAttempts());
    }

    public function testSaveHashesCode(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();

        $sut->save($otp, self::TEST_RAW_CODE);

        $found = $sut->find($this->testUserId);
        $this->assertNotNull($found);

        $expectedHash = hash('sha256', self::TEST_RAW_CODE . $this->testUserId);
        $this->assertSame($expectedHash, $found->getCode());
        $this->assertNotSame(self::TEST_RAW_CODE, $found->getCode());
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        $sut = $this->getSut();

        $this->assertNull($sut->find('nonexistent-user'));
    }

    public function testFindBySid(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();
        $sut->save($otp, self::TEST_RAW_CODE);

        $found = $sut->findBySid(self::TEST_SID);
        $this->assertNotNull($found);
        $this->assertSame($this->testUserId, $found->getUserId());
    }

    public function testFindBySidReturnsNullWhenNotFound(): void
    {
        $sut = $this->getSut();

        $this->assertNull($sut->findBySid('nonexistent-sid'));
    }

    public function testDelete(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();
        $sut->save($otp, self::TEST_RAW_CODE);

        $sut->delete($this->testUserId);

        $this->assertNull($sut->find($this->testUserId));
    }

    public function testIncrementAttempts(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();
        $sut->save($otp, self::TEST_RAW_CODE);

        $sut->incrementAttempts($this->testUserId);
        $sut->incrementAttempts($this->testUserId);

        $found = $sut->find($this->testUserId);
        $this->assertNotNull($found);
        $this->assertSame(2, $found->getAttempts());
    }

    public function testSavePreservesExpiresAt(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();
        $sut->save($otp, self::TEST_RAW_CODE);

        $found = $sut->find($this->testUserId);
        $this->assertNotNull($found);
        $this->assertSame(
            $otp->getExpiresAt()->format('Y-m-d H:i:s'),
            $found->getExpiresAt()->format('Y-m-d H:i:s')
        );
    }

    public function testSavePreservesLastSentAt(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();
        $sut->save($otp, self::TEST_RAW_CODE);

        $found = $sut->find($this->testUserId);
        $this->assertNotNull($found);
        $this->assertNotNull($found->getLastSentAt());
        $this->assertSame(
            $otp->getLastSentAt()->format('Y-m-d H:i:s'),
            $found->getLastSentAt()->format('Y-m-d H:i:s')
        );
    }

    public function testSaveWithNullLastSentAt(): void
    {
        $sut = $this->getSut();
        $otp = new OTP(
            userId: $this->testUserId,
            code: self::TEST_RAW_CODE,
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: 0,
            lastSentAt: null,
            sid: self::TEST_SID,
            context: 'frontend',
        );
        $sut->save($otp, self::TEST_RAW_CODE);

        $found = $sut->find($this->testUserId);
        $this->assertNotNull($found);
        $this->assertNull($found->getLastSentAt());
    }

    private function getSut(): OTPRepositoryInterface
    {
        return new OTPRepository(
            $this->get(QueryBuilderFactoryInterface::class)
        );
    }

    private function createOTP(): OTP
    {
        return new OTP(
            userId: $this->testUserId,
            code: self::TEST_RAW_CODE,
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: 0,
            lastSentAt: new \DateTimeImmutable(),
            sid: self::TEST_SID,
            context: 'frontend',
        );
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
