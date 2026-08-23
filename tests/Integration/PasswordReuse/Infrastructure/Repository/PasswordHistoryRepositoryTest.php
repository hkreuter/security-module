<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Infrastructure\Repository;

use DateTimeImmutable;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordHistoryRepositoryTest extends IntegrationTestCase
{
    #[Test]
    public function appendThenFindRecentHashesReturnsNewestFirstCappedAtLimit(): void
    {
        $sut = $this->getSut();
        $userId = $this->userId();

        $sut->append($userId, 'hash-oldest', new DateTimeImmutable('2026-01-01 10:00:00'));
        $sut->append($userId, 'hash-middle', new DateTimeImmutable('2026-01-02 10:00:00'));
        $sut->append($userId, 'hash-newest', new DateTimeImmutable('2026-01-03 10:00:00'));

        $this->assertSame(
            ['hash-newest', 'hash-middle', 'hash-oldest'],
            $sut->findRecentHashes($userId, 10),
            'Expected all entries newest-first when the limit exceeds the row count.'
        );

        $this->assertSame(
            ['hash-newest', 'hash-middle'],
            $sut->findRecentHashes($userId, 2),
            'Expected only the newest entries when the limit is below the row count.'
        );
    }

    #[Test]
    public function findRecentHashesIsKeyedByUserIdOnly(): void
    {
        $sut = $this->getSut();
        $userA = $this->userId();
        $userB = $this->userId();

        $sut->append($userA, 'a-hash', new DateTimeImmutable('2026-01-01 10:00:00'));
        $sut->append($userB, 'b-hash', new DateTimeImmutable('2026-01-01 10:00:00'));

        $this->assertSame(['a-hash'], $sut->findRecentHashes($userA, 10));
        $this->assertSame(['b-hash'], $sut->findRecentHashes($userB, 10));
    }

    #[Test]
    public function findRecentHashesReturnsEmptyForNonPositiveLimit(): void
    {
        $sut = $this->getSut();
        $userId = $this->userId();

        $sut->append($userId, 'a-hash', new DateTimeImmutable('2026-01-01 10:00:00'));

        $this->assertSame([], $sut->findRecentHashes($userId, 0));
    }

    #[Test]
    public function deleteSurplusBeyondKeepsNewestEntries(): void
    {
        $sut = $this->getSut();
        $userId = $this->userId();

        $sut->append($userId, 'h1-oldest', new DateTimeImmutable('2026-01-01 10:00:00'));
        $sut->append($userId, 'h2', new DateTimeImmutable('2026-01-02 10:00:00'));
        $sut->append($userId, 'h3', new DateTimeImmutable('2026-01-03 10:00:00'));
        $sut->append($userId, 'h4-newest', new DateTimeImmutable('2026-01-04 10:00:00'));

        $sut->deleteSurplusBeyond($userId, 2);

        $this->assertSame(
            ['h4-newest', 'h3'],
            $sut->findRecentHashes($userId, 10),
            'Expected exactly the newest 2 entries to survive.'
        );
    }

    #[Test]
    public function deleteSurplusBeyondIsNoOpWhenWithinLimit(): void
    {
        $sut = $this->getSut();
        $userId = $this->userId();

        $sut->append($userId, 'h1', new DateTimeImmutable('2026-01-01 10:00:00'));
        $sut->append($userId, 'h2', new DateTimeImmutable('2026-01-02 10:00:00'));

        $sut->deleteSurplusBeyond($userId, 5);

        $this->assertSame(['h2', 'h1'], $sut->findRecentHashes($userId, 10));
    }

    #[Test]
    public function purgeForUserRemovesOnlyThatUsersRows(): void
    {
        $sut = $this->getSut();
        $userA = $this->userId();
        $userB = $this->userId();

        $sut->append($userA, 'a-hash', new DateTimeImmutable('2026-01-01 10:00:00'));
        $sut->append($userB, 'b-hash', new DateTimeImmutable('2026-01-01 10:00:00'));

        $sut->purgeForUser($userA);

        $this->assertSame(0, $sut->countForUser($userA));
        $this->assertSame([], $sut->findRecentHashes($userA, 10));
        $this->assertSame(['b-hash'], $sut->findRecentHashes($userB, 10));
    }

    #[Test]
    public function purgeAllRemovesEveryUsersRows(): void
    {
        $sut = $this->getSut();
        $userA = $this->userId();
        $userB = $this->userId();

        $sut->append($userA, 'a-hash', new DateTimeImmutable('2026-01-01 10:00:00'));
        $sut->append($userB, 'b-hash', new DateTimeImmutable('2026-01-01 10:00:00'));

        $sut->purgeAll();

        $this->assertSame(0, $sut->countForUser($userA));
        $this->assertSame(0, $sut->countForUser($userB));
    }

    #[Test]
    public function countForUserReflectsAppendedRows(): void
    {
        $sut = $this->getSut();
        $userId = $this->userId();

        $this->assertSame(0, $sut->countForUser($userId));

        $sut->append($userId, 'h1', new DateTimeImmutable('2026-01-01 10:00:00'));
        $sut->append($userId, 'h2', new DateTimeImmutable('2026-01-02 10:00:00'));

        $this->assertSame(2, $sut->countForUser($userId));
    }

    private function getSut(): PasswordHistoryRepositoryInterface
    {
        return $this->get(PasswordHistoryRepositoryInterface::class);
    }

    private function userId(): string
    {
        return substr(uniqid('pwh', true), 0, 32);
    }
}
