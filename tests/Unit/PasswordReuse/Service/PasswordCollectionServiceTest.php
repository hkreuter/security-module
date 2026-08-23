<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing\PasswordHasherInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionService;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class PasswordCollectionServiceTest extends TestCase
{
    #[Test]
    public function candidateMatchingCurrentHashIsInCollectionEvenWithNoHistory(): void
    {
        $userId = uniqid('user_', true);
        $candidate = uniqid('plain_', true);
        $currentHash = uniqid('hash_', true);

        $repository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $repository->method('findRecentHashes')->willReturn([]);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verifyPassword')
            ->with($candidate, $currentHash)
            ->willReturn(true);

        $result = $this->getSut(hasher: $hasher, repository: $repository)
            ->isCandidateInCollection($userId, $candidate, $currentHash);

        $this->assertTrue($result);
    }

    #[Test]
    public function candidateMatchingAPreviousHashIsInCollection(): void
    {
        $candidate = uniqid('plain_', true);
        $currentHash = uniqid('hash_current_', true);
        $previousHashes = [uniqid('hash_prev1_', true), uniqid('hash_prev2_', true)];

        $repository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $repository->method('findRecentHashes')->willReturn($previousHashes);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verifyPassword')->willReturnCallback(
            static fn(string $plain, string $hash): bool => $hash === $previousHashes[1]
        );

        $result = $this->getSut(hasher: $hasher, repository: $repository)
            ->isCandidateInCollection(uniqid('user_', true), $candidate, $currentHash);

        $this->assertTrue($result);
    }

    #[Test]
    public function candidateMatchingNoMemberIsNotInCollection(): void
    {
        $repository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $repository->method('findRecentHashes')->willReturn([uniqid('hash_prev_', true)]);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verifyPassword')->willReturn(false);

        $result = $this->getSut(hasher: $hasher, repository: $repository)
            ->isCandidateInCollection(uniqid('user_', true), uniqid('plain_', true), uniqid('hash_', true));

        $this->assertFalse($result);
    }

    #[Test]
    public function collectionIsBoundedToResolvedSizeMinusOnePreviousEntries(): void
    {
        $userId = uniqid('user_', true);
        $rights = 'malladmin';
        $sizeN = mt_rand(3, 24);

        $account = $this->createStub(AccountDataInterface::class);
        $account->method('getRights')->willReturn($rights);

        $resolver = $this->createStub(AccountTypeResolverInterface::class);
        $resolver->method('resolveAccount')->willReturn($account);

        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->expects($this->once())
            ->method('resolveCollectionSizeForRights')
            ->with($rights)
            ->willReturn($sizeN);

        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findRecentHashes')
            ->with($userId, $sizeN - 1)
            ->willReturn([]);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verifyPassword')->willReturn(false);

        $this->getSut(hasher: $hasher, repository: $repository, settings: $settings, resolver: $resolver)
            ->isCandidateInCollection($userId, uniqid('plain_', true), uniqid('hash_', true));
    }

    #[Test]
    public function verificationUsesLegacyAwareHashPathNotStringEquality(): void
    {
        $candidate = 'secret-plaintext';
        $legacyCurrentHash = md5('secret-plaintext');

        $repository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $repository->method('findRecentHashes')->willReturn([]);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('verifyPassword')
            ->with($candidate, $legacyCurrentHash)
            ->willReturn(true);

        $this->assertNotSame($candidate, $legacyCurrentHash);

        $result = $this->getSut(hasher: $hasher, repository: $repository)
            ->isCandidateInCollection(uniqid('user_', true), $candidate, $legacyCurrentHash);

        $this->assertTrue($result);
    }

    #[Test]
    public function repositoryFailureRaisesFailClosedException(): void
    {
        $repository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $repository->method('findRecentHashes')->willThrowException(new RuntimeException('table read failed'));

        $this->expectException(PasswordReuseCheckException::class);

        $this->getSut(repository: $repository)
            ->isCandidateInCollection(uniqid('user_', true), uniqid('plain_', true), uniqid('hash_', true));
    }

    #[Test]
    public function verifyServiceFailureRaisesFailClosedException(): void
    {
        $repository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $repository->method('findRecentHashes')->willReturn([]);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verifyPassword')->willThrowException(new RuntimeException('hash service unavailable'));

        $this->expectException(PasswordReuseCheckException::class);

        $this->getSut(hasher: $hasher, repository: $repository)
            ->isCandidateInCollection(uniqid('user_', true), uniqid('plain_', true), uniqid('hash_', true));
    }

    #[Test]
    public function plaintextCandidateIsMarkedSensitiveToKeepItOutOfLogsAndTraces(): void
    {
        $parameters = (new ReflectionMethod(PasswordCollectionService::class, 'isCandidateInCollection'))
            ->getParameters();

        $candidateParameter = $parameters[1];

        $this->assertSame('candidate', $candidateParameter->getName());
        $this->assertNotEmpty(
            $candidateParameter->getAttributes(\SensitiveParameter::class),
            'The plaintext candidate must carry the SensitiveParameter attribute.'
        );
    }

    #[Test]
    public function implementsInterface(): void
    {
        $this->assertInstanceOf(PasswordCollectionServiceInterface::class, $this->getSut());
    }

    private function getSut(
        ?PasswordHasherInterface $hasher = null,
        ?PasswordHistoryRepositoryInterface $repository = null,
        ?ModuleSettingsServiceInterface $settings = null,
        ?AccountTypeResolverInterface $resolver = null,
    ): PasswordCollectionService {
        if ($settings === null) {
            $settingsStub = $this->createStub(ModuleSettingsServiceInterface::class);
            $settingsStub->method('resolveCollectionSizeForRights')->willReturn(mt_rand(3, 24));
            $settings = $settingsStub;
        }

        if ($resolver === null) {
            $account = $this->createStub(AccountDataInterface::class);
            $account->method('getRights')->willReturn('user');
            $resolverStub = $this->createStub(AccountTypeResolverInterface::class);
            $resolverStub->method('resolveAccount')->willReturn($account);
            $resolver = $resolverStub;
        }

        return new PasswordCollectionService(
            $hasher ?? $this->createStub(PasswordHasherInterface::class),
            $repository ?? $this->createStub(PasswordHistoryRepositoryInterface::class),
            $settings,
            $resolver,
        );
    }
}
