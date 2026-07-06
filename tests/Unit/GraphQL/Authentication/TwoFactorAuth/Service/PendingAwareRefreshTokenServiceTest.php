<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Authentication\TwoFactorAuth\Service;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\DataType\TwoFAPendingUser;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Service\PendingAwareRefreshTokenService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PendingAwareRefreshTokenServiceTest extends TestCase
{
    #[Test]
    public function createRefreshTokenReturnsEmptyStringForPendingUserAndDoesNotCallInner(): void
    {
        $pendingUser = new TwoFAPendingUser($this->createStub(EshopUserModel::class));

        $innerMock = $this->createMock(RefreshTokenServiceInterface::class);
        $innerMock->expects($this->never())->method('createRefreshTokenForUser');

        $sut = $this->getSut(inner: $innerMock);

        $this->assertSame('', $sut->createRefreshTokenForUser($pendingUser));
    }

    #[Test]
    public function createRefreshTokenDelegatesToInnerForRegularUser(): void
    {
        $regularUser = $this->createStub(UserInterface::class);
        $token = uniqid();

        $innerMock = $this->createMock(RefreshTokenServiceInterface::class);
        $innerMock->expects($this->once())
            ->method('createRefreshTokenForUser')
            ->with($regularUser)
            ->willReturn($token);

        $sut = $this->getSut(inner: $innerMock);

        $this->assertSame($token, $sut->createRefreshTokenForUser($regularUser));
    }

    #[Test]
    public function refreshTokenDelegatesToInner(): void
    {
        $refreshToken = uniqid();
        $fingerprintHash = uniqid();
        $newToken = uniqid();

        $innerMock = $this->createMock(RefreshTokenServiceInterface::class);
        $innerMock->expects($this->once())
            ->method('refreshToken')
            ->with($refreshToken, $fingerprintHash)
            ->willReturn($newToken);

        $sut = $this->getSut(inner: $innerMock);

        $this->assertSame($newToken, $sut->refreshToken($refreshToken, $fingerprintHash));
    }

    private function getSut(?RefreshTokenServiceInterface $inner = null): PendingAwareRefreshTokenService
    {
        return new PendingAwareRefreshTokenService(
            inner: $inner ?? $this->createStub(RefreshTokenServiceInterface::class),
        );
    }
}
