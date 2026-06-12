<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\OTP\Service;

use DateTimeImmutable;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\DTO\OtpChallengeStateInterface;
// phpcs:ignore Generic.Files.LineLength
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Infrastructure\Repository\OtpChallengeStateRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpChallengeStateService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeHasherServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OtpChallengeStateServiceTest extends TestCase
{
    #[Test]
    public function getChallengeStateProxiesToRepository(): void
    {
        $stateStub = $this->createStub(OtpChallengeStateInterface::class);

        $repositoryMock = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('findByUserId')
            ->with($userId = uniqid())
            ->willReturn($stateStub);

        $sut = $this->getSut(repository: $repositoryMock);

        $this->assertSame($stateStub, $sut->getChallengeState(userId: $userId));
    }

    #[Test]
    public function createChallengeStateHashesCodeAndPassesToRepository(): void
    {
        $lifetime = 120;
        $hasherMock = $this->createMock(OtpCodeHasherServiceInterface::class);
        $hasherMock->expects($this->once())
            ->method('hash')
            ->with($code = uniqid())
            ->willReturn($codeHash = uniqid());

        $repositorySpy = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositorySpy->expects($this->once())
            ->method('createChallengeState')
            ->with(
                $userId = uniqid(),
                $codeHash,
                $this->callback($this->expiresAtIsAround($lifetime))
            );

        $sut = $this->getSut(repository: $repositorySpy, hasher: $hasherMock, otpCodeLifetime: $lifetime);

        $sut->createChallengeState(userId: $userId, code: $code);
    }

    #[Test]
    public function refreshChallengeStateHashesCodeAndPassesToRepository(): void
    {
        $lifetime = 120;
        $hasherMock = $this->createMock(OtpCodeHasherServiceInterface::class);
        $hasherMock->expects($this->once())
            ->method('hash')
            ->with($code = uniqid())
            ->willReturn($codeHash = uniqid());

        $repositorySpy = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositorySpy->expects($this->once())
            ->method('refreshChallengeState')
            ->with(
                $userId = uniqid(),
                $codeHash,
                $this->callback($this->expiresAtIsAround($lifetime))
            );

        $sut = $this->getSut(repository: $repositorySpy, hasher: $hasherMock, otpCodeLifetime: $lifetime);

        $sut->refreshChallengeState(userId: $userId, code: $code);
    }

    /**
     * Predicate factory: expiresAt is now() + $lifetime seconds, allowing for a clock tick
     * during the call.
     */
    private function expiresAtIsAround(int $lifetime): callable
    {
        $before = (new DateTimeImmutable())->getTimestamp();
        return function (DateTimeImmutable $expiresAt) use ($lifetime, $before): bool {
            $after = (new DateTimeImmutable())->getTimestamp();
            $stamp = $expiresAt->getTimestamp();
            return $stamp >= $before + $lifetime && $stamp <= $after + $lifetime;
        };
    }

    #[Test]
    public function markVerifiedProxiesToRepository(): void
    {
        $repositorySpy = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositorySpy->expects($this->once())
            ->method('markVerified')
            ->with($userId = uniqid());

        $sut = $this->getSut(repository: $repositorySpy);

        $sut->markVerified(userId: $userId);
    }

    #[Test]
    public function incrementAttemptsProxiesToRepository(): void
    {
        $repositorySpy = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositorySpy->expects($this->once())
            ->method('incrementAttempts')
            ->with($userId = uniqid());

        $sut = $this->getSut(repository: $repositorySpy);

        $sut->incrementAttempts(userId: $userId);
    }

    #[Test]
    public function deleteChallengeStateProxiesToRepository(): void
    {
        $repositorySpy = $this->createMock(OtpChallengeStateRepositoryInterface::class);
        $repositorySpy->expects($this->once())
            ->method('deleteChallengeState')
            ->with($userId = uniqid());

        $sut = $this->getSut(repository: $repositorySpy);

        $sut->deleteChallengeState(userId: $userId);
    }

    private function getSut(
        OtpChallengeStateRepositoryInterface $repository = null,
        OtpCodeHasherServiceInterface $hasher = null,
        int $otpCodeLifetime = 300,
    ): OtpChallengeStateService {
        $settingsStub = $this->createStub(TwoFAShopSettingsInterface::class);
        $settingsStub->method('getOtpCodeLifetime')->willReturn($otpCodeLifetime);

        return new OtpChallengeStateService(
            stateRepository: $repository ?? $this->createStub(OtpChallengeStateRepositoryInterface::class),
            codeHasher: $hasher ?? $this->createStub(OtpCodeHasherServiceInterface::class),
            settings: $settingsStub,
        );
    }
}
