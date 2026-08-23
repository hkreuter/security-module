<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordHistoryService;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordHistoryServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PasswordHistoryServiceTest extends TestCase
{
    #[Test]
    public function recordAppendsSupersededHashThenPrunesToResolvedSizeMinusOne(): void
    {
        $userId = uniqid('user_', true);
        $hash = uniqid('hash_', true);
        $rights = 'malladmin';
        $sizeN = mt_rand(3, 24);
        $supersededAt = new DateTimeImmutable();

        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn(true);
        $settings->expects($this->once())
            ->method('resolveCollectionSizeForRights')
            ->with($rights)
            ->willReturn($sizeN);

        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('append')
            ->with($userId, $hash, $supersededAt);
        $repository->expects($this->once())
            ->method('deleteSurplusBeyond')
            ->with($userId, $sizeN - 1);

        $this->getSut(settings: $settings, repository: $repository)
            ->record($userId, $hash, $rights, $supersededAt);
    }

    #[Test]
    public function recordPrunesToZeroWhenResolvedSizeIsOne(): void
    {
        $userId = uniqid('user_', true);

        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn(true);
        $settings->method('resolveCollectionSizeForRights')->willReturn(1);

        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->once())->method('append');
        $repository->expects($this->once())
            ->method('deleteSurplusBeyond')
            ->with($userId, 0);

        $this->getSut(settings: $settings, repository: $repository)
            ->record($userId, uniqid('hash_', true), 'user', new DateTimeImmutable());
    }

    #[Test]
    public function recordIsANoOpWhenReusePreventionIsDisabled(): void
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn(false);

        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->never())->method('append');
        $repository->expects($this->never())->method('deleteSurplusBeyond');

        $this->getSut(settings: $settings, repository: $repository)
            ->record(uniqid('user_', true), uniqid('hash_', true), 'user', new DateTimeImmutable());
    }

    #[Test]
    public function recordLogsAndSwallowsRepositoryFailure(): void
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn(true);
        $settings->method('resolveCollectionSizeForRights')->willReturn(mt_rand(3, 24));

        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->method('append')->willThrowException(new RuntimeException('db down'));
        $repository->expects($this->never())->method('deleteSurplusBeyond');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $this->getSut(settings: $settings, repository: $repository, logger: $logger)
            ->record(uniqid('user_', true), uniqid('hash_', true), 'user', new DateTimeImmutable());
    }

    #[Test]
    public function purgeForUserDelegatesToRepository(): void
    {
        $userId = uniqid('user_', true);

        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('purgeForUser')
            ->with($userId);

        $this->getSut(repository: $repository)->purgeForUser($userId);
    }

    #[Test]
    public function implementsInterface(): void
    {
        $this->assertInstanceOf(PasswordHistoryServiceInterface::class, $this->getSut());
    }

    private function getSut(
        ?ModuleSettingsServiceInterface $settings = null,
        ?PasswordHistoryRepositoryInterface $repository = null,
        ?LoggerInterface $logger = null,
    ): PasswordHistoryService {
        if ($settings === null) {
            $settingsStub = $this->createStub(ModuleSettingsServiceInterface::class);
            $settingsStub->method('isReusePreventionEnabled')->willReturn(true);
            $settingsStub->method('resolveCollectionSizeForRights')->willReturn(mt_rand(3, 24));
            $settings = $settingsStub;
        }

        return new PasswordHistoryService(
            $settings,
            $repository ?? $this->createStub(PasswordHistoryRepositoryInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
