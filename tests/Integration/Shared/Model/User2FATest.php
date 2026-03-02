<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Shared\Model;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\Shared\Model\User2FAInterface;

final class User2FATest extends IntegrationTestCase
{
    private string $testUserId = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->testUserId = $this->getExistingUserId();
    }

    public function testUserImplementsUser2FAInterface(): void
    {
        $user = oxNew(User::class);

        $this->assertInstanceOf(User2FAInterface::class, $user);
    }

    public function testIs2FAEnabledReturnsFalseByDefault(): void
    {
        $user = oxNew(User::class);
        $user->load($this->testUserId);

        $this->assertFalse($user->is2FAEnabled());
    }

    public function testSet2FAEnabledToTrue(): void
    {
        $user = oxNew(User::class);
        $user->load($this->testUserId);

        $user->set2FAEnabled(true);
        $user->save();

        $reloaded = oxNew(User::class);
        $reloaded->load($this->testUserId);

        $this->assertTrue($reloaded->is2FAEnabled());
    }

    public function testSet2FAEnabledToFalse(): void
    {
        $user = oxNew(User::class);
        $user->load($this->testUserId);
        $user->set2FAEnabled(true);
        $user->save();

        $user->set2FAEnabled(false);
        $user->save();

        $reloaded = oxNew(User::class);
        $reloaded->load($this->testUserId);

        $this->assertFalse($reloaded->is2FAEnabled());
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
}
