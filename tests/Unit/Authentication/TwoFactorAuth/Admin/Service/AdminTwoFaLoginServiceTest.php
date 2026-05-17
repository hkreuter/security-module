<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Admin\Service;

use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\EshopCommunity\Internal\Framework\Session\SessionInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service\AdminTwoFaLoginService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AdminTwoFaLoginServiceTest extends TestCase
{
    #[Test]
    public function completeLoginSetsAuthInSessionInvalidatesChallengeAndRedirects(): void
    {
        $userId = uniqid('user_');

        $sessionSpy = $this->createMock(SessionInterface::class);
        $sessionSpy->expects($this->once())
            ->method('remove')
            ->with(AdminTwoFaLoginService::ADMIN_SESSION_KEY);
        $sessionSpy->expects($this->once())
            ->method('set')
            ->with('auth', $userId);

        $twoFAServiceSpy = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceSpy->expects($this->once())
            ->method('invalidateChallenge')
            ->with($userId);

        $oxSessionSpy = $this->createMock(Session::class);
        $oxSessionSpy->expects($this->once())->method('regenerateSessionId');
        $oxSessionSpy->method('sid')->willReturn('stoken=test');

        $utilsSpy = $this->createMock(Utils::class);
        $utilsSpy->expects($this->once())
            ->method('redirect')
            ->with($this->stringContains('admin_start'), false, 302);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            session: $sessionSpy,
            oxSession: $oxSessionSpy,
            utils: $utilsSpy,
        )->completeLogin($userId);
    }

    #[Test]
    public function completeLoginInvalidatesChallengeEvenWhenSessionSetThrows(): void
    {
        $userId = uniqid('user_');
        $sessionException = new RuntimeException('session boom');

        $sessionSpy = $this->createMock(SessionInterface::class);
        $sessionSpy->expects($this->once())
            ->method('remove')
            ->with(AdminTwoFaLoginService::ADMIN_SESSION_KEY);
        $sessionSpy->method('set')->willThrowException($sessionException);

        $twoFAServiceSpy = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceSpy->expects($this->once())
            ->method('invalidateChallenge')
            ->with($userId);

        $oxSessionSpy = $this->createMock(Session::class);
        $oxSessionSpy->expects($this->never())->method('regenerateSessionId');

        $utilsSpy = $this->createMock(Utils::class);
        $utilsSpy->expects($this->never())->method('redirect');

        $this->expectExceptionObject($sessionException);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            session: $sessionSpy,
            oxSession: $oxSessionSpy,
            utils: $utilsSpy,
        )->completeLogin($userId);
    }

    #[Test]
    public function abandonLoginRemovesSessionInvalidatesChallengeAndRedirects(): void
    {
        $userId = uniqid('user_');

        $sessionSpy = $this->createMock(SessionInterface::class);
        $sessionSpy->expects($this->once())
            ->method('remove')
            ->with(AdminTwoFaLoginService::ADMIN_SESSION_KEY);

        $twoFAServiceSpy = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceSpy->expects($this->once())
            ->method('invalidateChallenge')
            ->with($userId);

        $utilsSpy = $this->createMock(Utils::class);
        $utilsSpy->expects($this->once())
            ->method('redirect')
            ->with('index.php?cl=login', true, 302);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            session: $sessionSpy,
            utils: $utilsSpy,
        )->abandonLogin($userId);
    }

    #[Test]
    public function abandonLoginWithNullUserIdSkipsInvalidateAndRedirects(): void
    {
        $sessionSpy = $this->createMock(SessionInterface::class);
        $sessionSpy->expects($this->once())
            ->method('remove')
            ->with(AdminTwoFaLoginService::ADMIN_SESSION_KEY);

        $twoFAServiceSpy = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceSpy->expects($this->never())->method('invalidateChallenge');

        $utilsSpy = $this->createMock(Utils::class);
        $utilsSpy->expects($this->once())
            ->method('redirect')
            ->with('index.php?cl=login', true, 302);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            session: $sessionSpy,
            utils: $utilsSpy,
        )->abandonLogin(null);
    }

    #[Test]
    public function startChallengeSetsAdminSessionKeyAndTriggersOtp(): void
    {
        $userId = uniqid('user_');

        $sessionSpy = $this->createMock(SessionInterface::class);
        $sessionSpy->expects($this->once())
            ->method('set')
            ->with(AdminTwoFaLoginService::ADMIN_SESSION_KEY, $userId);

        $twoFAServiceSpy = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceSpy->expects($this->once())
            ->method('triggerChallenge')
            ->with($userId);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            session: $sessionSpy,
        )->startChallenge($userId);
    }

    private function getSut(
        TwoFAServiceInterface $twoFAService = null,
        SessionInterface $session = null,
        Session $oxSession = null,
        Utils $utils = null,
    ): AdminTwoFaLoginService {
        return new AdminTwoFaLoginService(
            twoFAService: $twoFAService ?? $this->createStub(TwoFAServiceInterface::class),
            session: $session ?? $this->createStub(SessionInterface::class),
            oxSession: $oxSession ?? $this->createStub(Session::class),
            utils: $utils ?? $this->createStub(Utils::class),
        );
    }
}
