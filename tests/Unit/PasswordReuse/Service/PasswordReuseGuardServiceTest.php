<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardService;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class PasswordReuseGuardServiceTest extends TestCase
{
    #[Test]
    public function disabledReusePreventionIsANoOpWithoutCheckingOrConfirming(): void
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn(false);

        $collection = $this->createMock(PasswordCollectionServiceInterface::class);
        $collection->expects($this->never())->method('isCandidateInCollection');

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $this->getSut(settings: $settings, collection: $collection, registry: $registry)
            ->guardChange(uniqid('user_', true), uniqid('plain_', true), uniqid('hash_', true));
    }

    #[Test]
    public function emptyCurrentHashIsInitialEstablishmentExemptFromCheckAndConfirm(): void
    {
        $collection = $this->createMock(PasswordCollectionServiceInterface::class);
        $collection->expects($this->never())->method('isCandidateInCollection');

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $this->getSut(collection: $collection, registry: $registry)
            ->guardChange(uniqid('user_', true), uniqid('plain_', true), '');
    }

    #[Test]
    public function reusedCandidateIsRejectedAndNotConfirmed(): void
    {
        $userId = uniqid('user_', true);
        $candidate = uniqid('plain_', true);
        $currentHash = uniqid('hash_', true);

        $collection = $this->createMock(PasswordCollectionServiceInterface::class);
        $collection->expects($this->once())
            ->method('isCandidateInCollection')
            ->with($userId, $candidate, $currentHash)
            ->willReturn(true);

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $this->expectException(PasswordReuseException::class);

        $this->getSut(collection: $collection, registry: $registry)
            ->guardChange($userId, $candidate, $currentHash);
    }

    #[Test]
    public function allowedGenuineChangeConfirmsTheChangeSignal(): void
    {
        $userId = uniqid('user_', true);
        $candidate = uniqid('plain_', true);
        $currentHash = uniqid('hash_', true);

        $collection = $this->createStub(PasswordCollectionServiceInterface::class);
        $collection->method('isCandidateInCollection')->willReturn(false);

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->once())->method('confirm')->with($userId);

        $this->getSut(collection: $collection, registry: $registry)
            ->guardChange($userId, $candidate, $currentHash);
    }

    #[Test]
    public function failClosedCheckExceptionPropagatesLoggedAndDoesNotConfirm(): void
    {
        $collection = $this->createStub(PasswordCollectionServiceInterface::class);
        $collection->method('isCandidateInCollection')
            ->willThrowException(new PasswordReuseCheckException());

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $this->expectException(PasswordReuseCheckException::class);

        $this->getSut(collection: $collection, registry: $registry, logger: $logger)
            ->guardChange(uniqid('user_', true), uniqid('plain_', true), uniqid('hash_', true));
    }

    #[Test]
    public function plaintextCandidateIsMarkedSensitiveToKeepItOutOfLogsAndTraces(): void
    {
        $parameters = (new ReflectionMethod(PasswordReuseGuardService::class, 'guardChange'))
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
        $this->assertInstanceOf(PasswordReuseGuardServiceInterface::class, $this->getSut());
    }

    private function getSut(
        ?ModuleSettingsServiceInterface $settings = null,
        ?PasswordCollectionServiceInterface $collection = null,
        ?ConfirmedChangeRegistryInterface $registry = null,
        ?LoggerInterface $logger = null,
    ): PasswordReuseGuardService {
        if ($settings === null) {
            $settingsStub = $this->createStub(ModuleSettingsServiceInterface::class);
            $settingsStub->method('isReusePreventionEnabled')->willReturn(true);
            $settings = $settingsStub;
        }

        return new PasswordReuseGuardService(
            $collection ?? $this->createStub(PasswordCollectionServiceInterface::class),
            $settings,
            $registry ?? $this->createStub(ConfirmedChangeRegistryInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
