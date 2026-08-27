<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Storefront\Customer\Service;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Storefront\Customer\DataType\Customer as CustomerDataType;
use OxidEsales\GraphQL\Storefront\Customer\Service\PasswordInterface;
use OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Exception\PasswordChangeRejected;
use OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Service\GuardedPasswordService;
use OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Service\PasswordChangeGuardInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Factory\UserModelFactoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Types\ID;

class GuardedPasswordServiceTest extends TestCase
{
    private const USER_ID = 'user-42';
    private const OLD = 'Current-pw-1!';
    private const NEW = 'Fresh-pw-9!X';

    #[Test]
    public function changeGuardsTheCandidateForAProvenChangeThenDelegates(): void
    {
        $guard = $this->createMock(PasswordChangeGuardInterface::class);
        $guard->expects($this->once())->method('guard')->with(self::USER_ID, self::NEW);

        $customer = $this->aCustomer();
        $inner = $this->createMock(PasswordInterface::class);
        $inner->expects($this->once())->method('change')->with(self::OLD, self::NEW)->willReturn($customer);

        $sut = $this->getSut(inner: $inner, guard: $guard);

        $this->assertSame($customer, $sut->change(self::OLD, self::NEW));
    }

    #[Test]
    public function changeDoesNotGuardWhenTheCurrentPasswordIsNotProven(): void
    {
        $guard = $this->createMock(PasswordChangeGuardInterface::class);
        $guard->expects($this->never())->method('guard');

        $inner = $this->createMock(PasswordInterface::class);
        $inner->expects($this->once())->method('change')->willReturn($this->aCustomer());

        $sut = $this->getSut(inner: $inner, guard: $guard, user: $this->userModel(samePassword: false));

        $sut->change(self::OLD, self::NEW);
    }

    #[Test]
    public function changeDoesNotGuardWhenNobodyIsAuthenticated(): void
    {
        $guard = $this->createMock(PasswordChangeGuardInterface::class);
        $guard->expects($this->never())->method('guard');

        $authentication = $this->createStub(AuthenticationServiceInterface::class);
        $authentication->method('getUser')->willReturn(null);

        $inner = $this->createMock(PasswordInterface::class);
        $inner->expects($this->once())->method('change')->willReturn($this->aCustomer());

        $sut = $this->getSut(inner: $inner, guard: $guard, authentication: $authentication);

        $sut->change(self::OLD, self::NEW);
    }

    #[Test]
    public function changePropagatesAGuardRejectionAndNeverDelegates(): void
    {
        $guard = $this->createStub(PasswordChangeGuardInterface::class);
        $guard->method('guard')->willThrowException(PasswordChangeRejected::reused());

        $inner = $this->createMock(PasswordInterface::class);
        $inner->expects($this->never())->method('change');

        $sut = $this->getSut(inner: $inner, guard: $guard);

        $this->expectException(PasswordChangeRejected::class);

        $sut->change(self::OLD, self::NEW);
    }

    #[Test]
    public function resetGuardsTheCandidateWhenTheHashResolvesThenDelegates(): void
    {
        $guard = $this->createMock(PasswordChangeGuardInterface::class);
        $guard->expects($this->once())->method('guard')->with(self::USER_ID, self::NEW);

        $inner = $this->createMock(PasswordInterface::class);
        $inner->expects($this->once())->method('resetPasswordByUpdateHash')->with('hash', self::NEW, self::NEW)
            ->willReturn(true);

        $sut = $this->getSut(inner: $inner, guard: $guard);

        $this->assertTrue($sut->resetPasswordByUpdateHash('hash', self::NEW, self::NEW));
    }

    #[Test]
    public function resetDoesNotGuardWhenTheUpdateHashResolvesToNoUser(): void
    {
        $guard = $this->createMock(PasswordChangeGuardInterface::class);
        $guard->expects($this->never())->method('guard');

        $inner = $this->createMock(PasswordInterface::class);
        $inner->expects($this->once())->method('resetPasswordByUpdateHash')->willReturn(false);

        $sut = $this->getSut(inner: $inner, guard: $guard, user: $this->userModel(loadedByUpdateId: false));

        $this->assertFalse($sut->resetPasswordByUpdateHash('bogus', self::NEW, self::NEW));
    }

    #[Test]
    public function sendPasswordForgotEmailJustDelegates(): void
    {
        $inner = $this->createMock(PasswordInterface::class);
        $inner->expects($this->once())->method('sendPasswordForgotEmail')->with('a@b.tld')->willReturn(true);

        $this->assertTrue($this->getSut(inner: $inner)->sendPasswordForgotEmail('a@b.tld'));
    }

    private function aCustomer(): CustomerDataType
    {
        return new CustomerDataType($this->createStub(User::class));
    }

    private function userModel(bool $samePassword = true, bool $loadedByUpdateId = true): User
    {
        $user = $this->createStub(User::class);
        $user->method('load')->willReturn(true);
        $user->method('loadUserByUpdateId')->willReturn($loadedByUpdateId);
        $user->method('isSamePassword')->willReturn($samePassword);
        $user->method('getId')->willReturn(self::USER_ID);

        return $user;
    }

    private function getSut(
        ?PasswordInterface $inner = null,
        ?AuthenticationServiceInterface $authentication = null,
        ?User $user = null,
        ?PasswordChangeGuardInterface $guard = null,
    ): GuardedPasswordService {
        if ($authentication === null) {
            $authUser = $this->createStub(UserInterface::class);
            $authUser->method('id')->willReturn(new ID(self::USER_ID));
            $authentication = $this->createStub(AuthenticationServiceInterface::class);
            $authentication->method('getUser')->willReturn($authUser);
        }

        $factory = $this->createStub(UserModelFactoryInterface::class);
        $factory->method('create')->willReturn($user ?? $this->userModel());

        return new GuardedPasswordService(
            $inner ?? $this->createStub(PasswordInterface::class),
            $authentication,
            $factory,
            $guard ?? $this->createStub(PasswordChangeGuardInterface::class),
        );
    }
}
