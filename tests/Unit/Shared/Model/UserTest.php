<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Shared\Model;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAUserServiceInterface;
use OxidEsales\SecurityModule\Shared\Model\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class UserTest extends TestCase
{
    // ----------------------------------------------------------------
    // Admin branch — admin 2FA is handled by AdminLoginController,
    // User::onLogin() must not start a challenge for admin users.
    // ----------------------------------------------------------------

    #[Test]
    public function onLoginAdminBranchNeverStartsChallengeRegardlessOfServiceState(): void
    {
        $userId = uniqid('user_');

        $twoFAUserServiceSpy = $this->createMock(TwoFAUserServiceInterface::class);
        $twoFAUserServiceSpy->expects($this->never())->method('startChallengeForUser');

        $this->callOnLogin($this->buildAdminUserMock($userId, $twoFAUserServiceSpy));
    }

    // ----------------------------------------------------------------
    // Frontend branch
    // ----------------------------------------------------------------

    #[Test]
    public function onLoginFrontendBranchCallsStartChallengeForUserWhenTwoFaRequiredAndNotVerified(): void
    {
        $userId = uniqid('user_');

        $twoFAUserServiceSpy = $this->createMock(TwoFAUserServiceInterface::class);
        $twoFAUserServiceSpy->method('isTwoFARequired')->with($userId)->willReturn(true);
        $twoFAUserServiceSpy->method('isChallengeVerified')->with($userId)->willReturn(false);
        $twoFAUserServiceSpy->expects($this->once())
            ->method('startChallengeForUser')
            ->with($userId);

        $this->callOnLogin(
            $this->buildFrontendUserMock($userId, $twoFAUserServiceSpy)
        );
    }

    #[Test]
    public function onLoginFrontendBranchDoesNotStartChallengeWhenTwoFaNotRequired(): void
    {
        $userId = uniqid('user_');

        $twoFAUserServiceSpy = $this->createMock(TwoFAUserServiceInterface::class);
        $twoFAUserServiceSpy->method('isTwoFARequired')->with($userId)->willReturn(false);
        $twoFAUserServiceSpy->expects($this->never())->method('startChallengeForUser');

        $this->callOnLogin(
            $this->buildFrontendUserMock($userId, $twoFAUserServiceSpy)
        );
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Invokes the protected User::onLogin() method via PHP Reflection.
     * (OXID's Base::__call() magic only works when OXID_PHP_UNIT is defined.)
     */
    private function callOnLogin(User $sut): void
    {
        $method = new ReflectionMethod($sut, 'onLogin');
        $method->invoke($sut, 'user@example.com', 'secret');
    }

    /**
     * Builds a partial mock of User for the ADMIN context.
     * - callParentOnLogin() → no-op
     * - isAdmin()           → true
     * - getId()             → $userId
     * - getService()        → returns $twoFAUserService (should never be called for admin)
     */
    private function buildAdminUserMock(
        string $userId,
        TwoFAUserServiceInterface $twoFAUserService,
    ): User {
        $sut = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['callParentOnLogin', 'isAdmin', 'getId', 'getService'])
            ->getMock();

        $sut->method('callParentOnLogin'); // no-op
        $sut->method('isAdmin')->willReturn(true);
        $sut->method('getId')->willReturn($userId);
        $sut->method('getService')
            ->willReturnCallback(
                fn(string $id) => match ($id) {
                    TwoFAUserServiceInterface::class => $twoFAUserService,
                    default                          => throw new \LogicException("Unexpected service: $id"),
                }
            );

        return $sut;
    }

    /**
     * Builds a partial mock of User for the FRONTEND context.
     * - callParentOnLogin() → no-op
     * - isAdmin()           → false
     * - getId()             → $userId
     * - getService()        → returns $twoFAUserService
     */
    private function buildFrontendUserMock(
        string $userId,
        TwoFAUserServiceInterface $twoFAUserService,
    ): User {
        $sut = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['callParentOnLogin', 'isAdmin', 'getId', 'getService'])
            ->getMock();

        $sut->method('callParentOnLogin'); // no-op
        $sut->method('isAdmin')->willReturn(false);
        $sut->method('getId')->willReturn($userId);
        $sut->method('getService')
            ->willReturnCallback(
                fn(string $id) => match ($id) {
                    TwoFAUserServiceInterface::class => $twoFAUserService,
                    default                          => throw new \LogicException("Unexpected service: $id"),
                }
            );

        return $sut;
    }
}
