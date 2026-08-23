<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountData;
use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\AccountRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolver;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AccountTypeResolverTest extends TestCase
{
    #[Test]
    public function isAdminReturnsFalseWhenRightsAreUser(): void
    {
        $sut = $this->getSut(account: $this->makeAccount(rights: 'user'));

        $this->assertFalse($sut->isAdmin(userId: uniqid()));
    }

    #[Test]
    public function isAdminReturnsFalseWhenRightsAreEmpty(): void
    {
        $sut = $this->getSut(account: $this->makeAccount(rights: ''));

        $this->assertFalse($sut->isAdmin(userId: uniqid()));
    }

    #[Test]
    public function isAdminReturnsTrueWhenRightsAreMalladmin(): void
    {
        $sut = $this->getSut(account: $this->makeAccount(rights: 'malladmin'));

        $this->assertTrue($sut->isAdmin(userId: uniqid()));
    }

    #[Test]
    public function isAdminReturnsTrueWhenRightsAreASubshopId(): void
    {
        $sut = $this->getSut(account: $this->makeAccount(rights: (string)mt_rand(2, 99)));

        $this->assertTrue($sut->isAdmin(userId: uniqid()));
    }

    #[Test]
    public function isAdminReturnsTrueWhenRightsAreUnknown(): void
    {
        $sut = $this->getSut(account: $this->makeAccount(rights: uniqid('role_', true)));

        $this->assertTrue($sut->isAdmin(userId: uniqid()));
    }

    #[Test]
    public function resolveAccountReturnsRecipientAndLocaleOfTheAccountData(): void
    {
        $email = uniqid('mail_', true);
        $languageId = mt_rand(0, 5);
        $shopId = mt_rand(1, 9);

        $sut = $this->getSut(
            account: $this->makeAccount(
                email: $email,
                languageId: $languageId,
                shopId: $shopId,
            ),
        );

        $affectedAccount = $sut->resolveAccount(userId: uniqid());

        $this->assertSame($email, $affectedAccount->getEmail());
        $this->assertSame($languageId, $affectedAccount->getLanguageId());
        $this->assertSame($shopId, $affectedAccount->getShopId());
    }

    #[Test]
    public function resolveAccountLoadsTheRequestedUser(): void
    {
        $userId = uniqid('user_', true);

        $accountRepositoryMock = $this->createMock(AccountRepositoryInterface::class);
        $accountRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($this->makeAccount());

        $sut = $this->getSut(accountRepository: $accountRepositoryMock);

        $sut->resolveAccount(userId: $userId);
    }

    #[Test]
    public function implementsInterface(): void
    {
        $this->assertInstanceOf(AccountTypeResolverInterface::class, $this->getSut());
    }

    private function makeAccount(
        string $rights = 'user',
        ?string $email = null,
        ?int $languageId = null,
        ?int $shopId = null,
    ): AccountDataInterface {
        return new AccountData(
            userId: uniqid('user_', true),
            email: $email ?? uniqid('mail_', true),
            rights: $rights,
            languageId: $languageId ?? mt_rand(0, 5),
            shopId: $shopId ?? mt_rand(1, 9),
        );
    }

    private function getSut(
        ?AccountRepositoryInterface $accountRepository = null,
        ?AccountDataInterface $account = null,
    ): AccountTypeResolver {
        if ($accountRepository === null) {
            $accountRepositoryStub = $this->createStub(AccountRepositoryInterface::class);
            $accountRepositoryStub->method('getById')
                ->willReturn($account ?? $this->makeAccount());
            $accountRepository = $accountRepositoryStub;
        }

        return new AccountTypeResolver(
            accountRepository: $accountRepository,
        );
    }
}
