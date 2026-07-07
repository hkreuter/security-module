<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\OTP\Service;

use DateTimeImmutable;
use Generator;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\DTO\OtpChallengeStateInterface;
// phpcs:ignore Generic.Files.LineLength
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Infrastructure\Repository\OtpChallengeStateRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpSendPolicyService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpSendPolicyServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OtpSendPolicyServiceTest extends TestCase
{
    #[Test]
    public function canSendReturnsTrueWhenNoStateExists(): void
    {
        $userId = uniqid();

        $repositoryMock = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositoryMock->method('findByUserId')
            ->with($userId)
            ->willReturn(null);

        $sut = $this->getSut(stateRepository: $repositoryMock);

        $this->assertTrue($sut->canSend($userId));
    }

    #[Test]
    public function canSendReturnsTrueWhenLastSentAtIsNull(): void
    {
        $userId = uniqid();

        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getLastSentAt')
            ->willReturn(null);

        $repositoryMock = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositoryMock->method('findByUserId')
            ->with($userId)
            ->willReturn($stateStub);

        $sut = $this->getSut(stateRepository: $repositoryMock);

        $this->assertTrue($sut->canSend($userId));
    }

    #[Test]
    public function canSendReturnsTrueWhenCooldownHasPassed(): void
    {
        $userId = uniqid();

        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getLastSentAt')
            ->willReturn(new DateTimeImmutable('-61 seconds'));

        $repositoryMock = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositoryMock->method('findByUserId')
            ->with($userId)
            ->willReturn($stateStub);

        $sut = $this->getSut(stateRepository: $repositoryMock);

        $this->assertTrue($sut->canSend($userId));
    }

    #[Test]
    public function canSendReturnsFalseWhenCooldownHasNotPassed(): void
    {
        $userId = uniqid();

        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getLastSentAt')
            ->willReturn(new DateTimeImmutable('-30 seconds'));

        $repositoryMock = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositoryMock->method('findByUserId')
            ->with($userId)
            ->willReturn($stateStub);

        $sut = $this->getSut(stateRepository: $repositoryMock);

        $this->assertFalse($sut->canSend($userId));
    }

    #[Test]
    public function getCooldownRemainingReturnsZeroWhenNoStateExists(): void
    {
        $userId = uniqid();

        $repositoryMock = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositoryMock->method('findByUserId')
            ->with($userId)
            ->willReturn(null);

        $this->assertSame(0, $this->getSut(stateRepository: $repositoryMock)->getCooldownRemaining($userId));
    }

    #[Test]
    #[DataProvider('getCooldownRemainingWithStateDataProvider')]
    public function getCooldownRemainingReturnsExpectedValueWithState(
        ?int $lastSentAtOffsetSeconds,
        int $expectedRemaining,
    ): void {
        $userId = uniqid();

        // Build the timestamp here (at execution time), not in the data provider: PHPUnit
        // collects all data providers up front, so a DateTimeImmutable frozen there drifts
        // by however long the rest of the suite takes to reach this test.
        $lastSentAt = $lastSentAtOffsetSeconds === null
            ? null
            : new DateTimeImmutable("-{$lastSentAtOffsetSeconds} seconds");

        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getLastSentAt')->willReturn($lastSentAt);

        $repositoryMock = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositoryMock->method('findByUserId')
            ->with($userId)
            ->willReturn($stateStub);

        $this->assertEqualsWithDelta(
            $expectedRemaining,
            $this->getSut(stateRepository: $repositoryMock)->getCooldownRemaining($userId),
            1,
        );
    }

    public static function getCooldownRemainingWithStateDataProvider(): Generator
    {
        yield 'null lastSentAt returns zero' => [null, 0];
        yield 'cooldown passed returns zero' => [61, 0];
        yield 'cooldown active returns remaining seconds' => [30, 30];
    }

    private function getSut(
        OtpChallengeStateRepositoryInterface $stateRepository = null,
    ): OtpSendPolicyServiceInterface {
        return new OtpSendPolicyService(
            stateRepository: $stateRepository
                ?? $this->createStub(OtpChallengeStateRepositoryInterface::class),
        );
    }
}
