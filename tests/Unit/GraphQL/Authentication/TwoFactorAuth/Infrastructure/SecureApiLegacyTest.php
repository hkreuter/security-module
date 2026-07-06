<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Authentication\TwoFactorAuth\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorRequiredException;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\DataType\TwoFAPendingUser;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Infrastructure\SecureApiLegacy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SecureApiLegacyTest extends TestCase
{
    #[Test]
    public function loginReturnsTwoFAPendingUserWhenTwoFactorRequired(): void
    {
        $userId = uniqid();
        $userModelStub = $this->createStub(EshopUserModel::class);
        $userModelStub->method('login')
            ->willThrowException(new TwoFactorRequiredException(uniqid(), uniqid()));
        $userModelStub->method('getId')->willReturn($userId);

        $innerStub = $this->createStub(Legacy::class);
        $innerStub->method('getUserModel')->willReturn($userModelStub);

        $sut = $this->getSut(inner: $innerStub);

        $result = $sut->login(uniqid(), uniqid());

        $this->assertInstanceOf(TwoFAPendingUser::class, $result);
        $this->assertSame($userId, (string)$result->id()->val());
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
    public function getShopIdDelegatesToTheInnerLegacy(): void
    {
        $shopId = mt_rand(1, 99);
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getShopId')->willReturn($shopId);

        $this->assertSame($shopId, $this->getSut(inner: $innerMock)->getShopId());
    }

    #[Test]
    public function getUserModelDelegatesTheUserIdToTheInnerLegacy(): void
    {
        $userId = uniqid();
        $model = $this->createStub(EshopUserModel::class);
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getUserModel')->with($userId)->willReturn($model);

        $this->assertSame($model, $this->getSut(inner: $innerMock)->getUserModel($userId));
    }

    #[Test]
    public function getConfigParamDelegatesToTheInnerLegacy(): void
    {
        $param = uniqid();
        $value = uniqid();
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getConfigParam')->with($param)->willReturn($value);

        $this->assertSame($value, $this->getSut(inner: $innerMock)->getConfigParam($param));
    }

    #[Test]
    public function getShopUrlDelegatesToTheInnerLegacy(): void
    {
        $url = 'https://' . uniqid() . '.example.com/';
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getShopUrl')->willReturn($url);

        $this->assertSame($url, $this->getSut(inner: $innerMock)->getShopUrl());
    }

    #[Test]
    public function getLanguageIdDelegatesToTheInnerLegacy(): void
    {
        $languageId = mt_rand(0, 9);
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getLanguageId')->willReturn($languageId);

        $this->assertSame($languageId, $this->getSut(inner: $innerMock)->getLanguageId());
    }

    #[Test]
    public function isValidEmailDelegatesToTheInnerLegacy(): void
    {
        $email = uniqid() . '@example.com';
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('isValidEmail')->with($email)->willReturn(true);

        $this->assertTrue($this->getSut(inner: $innerMock)->isValidEmail($email));
    }

    #[Test]
    public function getEmailDelegatesToTheInnerLegacy(): void
    {
        $emailStub = $this->createStub(Email::class);
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getEmail')->willReturn($emailStub);

        $this->assertSame($emailStub, $this->getSut(inner: $innerMock)->getEmail());
    }

    #[Test]
    public function getUserGroupIdsDelegatesTheUserIdToTheInnerLegacy(): void
    {
        $userId = uniqid();
        $groupIds = [uniqid(), uniqid()];
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('getUserGroupIds')->with($userId)->willReturn($groupIds);

        $this->assertSame($groupIds, $this->getSut(inner: $innerMock)->getUserGroupIds($userId));
    }

    #[Test]
    public function createUniqueIdentifierDelegatesToTheInnerLegacy(): void
    {
        $identifier = uniqid();
        $innerMock = $this->createMock(Legacy::class);
        $innerMock->expects($this->once())->method('createUniqueIdentifier')->willReturn($identifier);

        $this->assertSame($identifier, $this->getSut(inner: $innerMock)->createUniqueIdentifier());
    }

    private function getSut(?Legacy $inner = null): SecureApiLegacy
    {
        return new SecureApiLegacy(
            inner: $inner ?? $this->createStub(Legacy::class),
        );
    }
}
