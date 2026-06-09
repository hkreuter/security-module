<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorRequiredException;
use OxidEsales\SecurityModule\GraphQL\DataType\TwoFAPendingUser;
use OxidEsales\SecurityModule\GraphQL\Infrastructure\SecureApiLegacy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SecureApiLegacyTest extends TestCase
{
    #[Test]
    public function loginReturnsTwoFAPendingUserWhenTwoFactorRequired(): void
    {
        $userModelStub = $this->createStub(EshopUserModel::class);
        $userModelStub->method('login')
            ->willThrowException(new TwoFactorRequiredException(uniqid(), uniqid()));

        $innerStub = $this->createStub(Legacy::class);
        $innerStub->method('getUserModel')->willReturn($userModelStub);

        $sut = $this->getSut(inner: $innerStub);

        $result = $sut->login(uniqid(), uniqid());

        $this->assertInstanceOf(TwoFAPendingUser::class, $result);
        $this->assertSame($userModelStub, $result->getEshopModel());
    }

    #[Test]
    public function loginThrowsInvalidLoginWhenCredentialsAreInvalid(): void
    {
        $userModelStub = $this->createStub(EshopUserModel::class);
        $userModelStub->method('login')->willThrowException(new Exception('invalid'));

        $innerStub = $this->createStub(Legacy::class);
        $innerStub->method('getUserModel')->willReturn($userModelStub);

        $sut = $this->getSut(inner: $innerStub);

        $this->expectException(InvalidLogin::class);

        $sut->login(uniqid(), uniqid());
    }

    #[Test]
    public function loginReturnsAuthenticatedUserOnSuccess(): void
    {
        $userModelStub = $this->createStub(EshopUserModel::class);

        $innerStub = $this->createStub(Legacy::class);
        $innerStub->method('getUserModel')->willReturn($userModelStub);

        $sut = $this->getSut(inner: $innerStub);

        $result = $sut->login(uniqid(), uniqid());

        $this->assertInstanceOf(User::class, $result);
        $this->assertFalse($result->isAnonymous());
    }

    #[Test]
    public function loginReturnsAnonymousUserWhenNoCredentialsGiven(): void
    {
        $userModelStub = $this->createStub(EshopUserModel::class);

        $innerStub = $this->createStub(Legacy::class);
        $innerStub->method('getUserModel')->willReturn($userModelStub);
        $innerStub->method('createUniqueIdentifier')->willReturn(uniqid());

        $sut = $this->getSut(inner: $innerStub);

        $result = $sut->login();

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->isAnonymous());
    }

    #[Test]
    public function nonLoginCallsDelegateToTheInnerLegacy(): void
    {
        $shopId = mt_rand(1, 99);
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getShopId')->willReturn($shopId);

        $sut = $this->getSut(inner: $innerMock);

        $this->assertSame($shopId, $sut->getShopId());
    }

    private function getSut(?Legacy $inner = null): SecureApiLegacy
    {
        return new SecureApiLegacy(
            inner: $inner ?? $this->createStub(Legacy::class),
        );
    }
}
