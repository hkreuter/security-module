<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\DataType;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\SecurityModule\GraphQL\DataType\TwoFAPendingUser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TwoFAPendingUserTest extends TestCase
{
    #[Test]
    public function idReturnsTheWrappedUserModelIdAsGraphQLId(): void
    {
        $userId = uniqid();
        $userModelStub = $this->createStub(EshopUserModel::class);
        $userModelStub->method('getId')->willReturn($userId);

        $sut = $this->getSut(userModel: $userModelStub);

        $this->assertSame($userId, (string)$sut->id()->val());
    }

    #[Test]
    public function emailReturnsTheWrappedUserModelUsername(): void
    {
        $email = uniqid() . '@example.com';
        $userModelStub = $this->createStub(EshopUserModel::class);
        $userModelStub->method('getRawFieldData')->willReturn($email);

        $sut = $this->getSut(userModel: $userModelStub);

        $this->assertSame($email, $sut->email());
    }

    #[Test]
    public function isAnonymousAlwaysReturnsTrue(): void
    {
        $this->assertTrue($this->getSut()->isAnonymous());
    }

    #[Test]
    public function getEshopModelReturnsTheWrappedModel(): void
    {
        $userModelStub = $this->createStub(EshopUserModel::class);

        $sut = $this->getSut(userModel: $userModelStub);

        $this->assertSame($userModelStub, $sut->getEshopModel());
    }

    private function getSut(?EshopUserModel $userModel = null): TwoFAPendingUser
    {
        return new TwoFAPendingUser(
            userModel: $userModel ?? $this->createStub(EshopUserModel::class),
        );
    }
}
