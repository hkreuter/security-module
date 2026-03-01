<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Authentication\TwoFactorAuth\Infrastructure\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepository;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;

class OTPRepositoryTest extends IntegrationTestCase
{
    private const TEST_USER_ID = '_testuser2fa';
    private const TEST_SID = 'testsid12345678901234567890ab';

    public function setUp(): void
    {
        parent::setUp();
        $this->cleanUp2faTable();
        $this->ensureTestUserExists();
    }

    public function tearDown(): void
    {
        $this->cleanUp2faTable();
        $this->cleanUpTestUser();
        parent::tearDown();
    }

    public function testSaveAndFind(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();

        $sut->save($otp, '123456');

        $found = $sut->find(self::TEST_USER_ID);

        $this->assertNotNull($found);
        $this->assertSame(self::TEST_USER_ID, $found->userId);
        $this->assertSame(0, $found->attempts);
        $this->assertSame(self::TEST_SID, $found->sid);
        $this->assertSame('frontend', $found->context);
    }

    public function testSaveHashesCode(): void
    {
        $sut = $this->getSut();
        $otp = $this->createOTP();
        $rawCode = '123456';

        $sut->save($otp, $rawCode);

        $found = $sut->find(self::TEST_USER_ID);
        $expectedHash = hash('sha256', $rawCode . self::TEST_USER_ID);

        $this->assertSame($expectedHash, $found->code);
    }

    public function testFindReturnsNullForNonExistent(): void
    {
        $sut = $this->getSut();

        $this->assertNull($sut->find('nonexistent'));
    }

    public function testFindBySid(): void
    {
        $sut = $this->getSut();
        $sut->save($this->createOTP(), '123456');

        $found = $sut->findBySid(self::TEST_SID);

        $this->assertNotNull($found);
        $this->assertSame(self::TEST_USER_ID, $found->userId);
    }

    public function testFindBySidReturnsNullForNonExistent(): void
    {
        $sut = $this->getSut();

        $this->assertNull($sut->findBySid('nonexistent'));
    }

    public function testDelete(): void
    {
        $sut = $this->getSut();
        $sut->save($this->createOTP(), '123456');

        $sut->delete(self::TEST_USER_ID);

        $this->assertNull($sut->find(self::TEST_USER_ID));
    }

    public function testIncrementAttempts(): void
    {
        $sut = $this->getSut();
        $sut->save($this->createOTP(), '123456');

        $sut->incrementAttempts(self::TEST_USER_ID);
        $sut->incrementAttempts(self::TEST_USER_ID);

        $found = $sut->find(self::TEST_USER_ID);
        $this->assertSame(2, $found->attempts);
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
            userId: self::TEST_USER_ID,
            code: '123456',
            expiresAt: new \DateTimeImmutable('+5 minutes'),
            attempts: 0,
            lastSentAt: null,
            sid: self::TEST_SID,
            context: 'frontend',
        );
    }

    private function ensureTestUserExists(): void
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $result = $queryBuilder->select('OXID')
            ->from('oxuser')
            ->where('OXID = :oxid')
            ->setParameter('oxid', self::TEST_USER_ID)
            ->execute();

        if (is_object($result) && !$result->fetchOne()) {
            $qb = $this->get(QueryBuilderFactoryInterface::class)->create();
            $qb->insert('oxuser')
                ->values([
                    'OXID' => ':oxid',
                    'OXUSERNAME' => ':username',
                    'OXACTIVE' => ':active',
                    'OXRIGHTS' => ':rights',
                    'OXPASSWORD' => ':password',
                    'OXPASSSALT' => ':salt',
                ])
                ->setParameters([
                    'oxid' => self::TEST_USER_ID,
                    'username' => 'test2fa@example.com',
                    'active' => 1,
                    'rights' => 'user',
                    'password' => '',
                    'salt' => '',
                ])
                ->execute();
        }
    }

    private function cleanUpTestUser(): void
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->delete('oxuser')
            ->where('OXID = :oxid')
            ->setParameter('oxid', self::TEST_USER_ID)
            ->execute();
    }

    private function cleanUp2faTable(): void
    {
        $queryBuilder = $this->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->delete('oe_security_2fa')->execute();
    }
}
