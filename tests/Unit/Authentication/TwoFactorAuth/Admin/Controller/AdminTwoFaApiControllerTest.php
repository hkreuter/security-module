<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Admin\Controller;

use OxidEsales\EshopCommunity\Internal\Framework\Session\SessionInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller\AdminTwoFaApiController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\AttemptLimitExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\ResendCooldownException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAUserService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AdminTwoFaApiControllerTest extends TestCase
{
    #[Test]
    public function resendReturns401WhenNoPendingUserInSession(): void
    {
        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => null]);

        $response = $this->getSut(session: $sessionStub)->resend();

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
    }

    #[Test]
    public function resendReturnsSuccessJsonWithRemainingAttemptsOnSuccess(): void
    {
        $userId = uniqid('user_');
        $remainingAttempts = 3;
        $cooldownRemaining = 60;

        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => $userId]);

        $twoFAService = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAService->expects($this->once())
            ->method('resend')
            ->with($userId);
        $twoFAService->method('getRemainingAttempts')
            ->with($userId)
            ->willReturn($remainingAttempts);
        $twoFAService->method('getCooldownRemaining')
            ->with($userId)
            ->willReturn($cooldownRemaining);

        $response = $this->getSut(
            session: $sessionStub,
            twoFAService: $twoFAService,
        )->resend();

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertSame($remainingAttempts, $body['remainingAttempts']);
        $this->assertSame($cooldownRemaining, $body['cooldownRemaining']);
    }

    #[Test]
    public function resendReturns429WithCooldownOnResendCooldownException(): void
    {
        $userId = uniqid('user_');
        $cooldownRemaining = 45;

        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => $userId]);

        $twoFAService = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAService->method('resend')
            ->with($userId)
            ->willThrowException(new ResendCooldownException());
        $twoFAService->method('getCooldownRemaining')
            ->with($userId)
            ->willReturn($cooldownRemaining);

        $response = $this->getSut(
            session: $sessionStub,
            twoFAService: $twoFAService,
        )->resend();

        $this->assertSame(429, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertSame($cooldownRemaining, $body['cooldownRemaining']);
    }

    #[Test]
    public function resendReturns429WhenAttemptLimitExceeded(): void
    {
        $userId = uniqid('user_');

        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => $userId]);

        $twoFAService = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAService->method('resend')
            ->with($userId)
            ->willThrowException(new AttemptLimitExceededException());

        $response = $this->getSut(
            session: $sessionStub,
            twoFAService: $twoFAService,
        )->resend();

        $this->assertSame(429, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
    }

    private function getSut(
        SessionInterface $session = null,
        TwoFAServiceInterface $twoFAService = null,
    ): AdminTwoFaApiController {
        return new AdminTwoFaApiController(
            session: $session ?? $this->createStub(SessionInterface::class),
            twoFAService: $twoFAService ?? $this->createStub(TwoFAServiceInterface::class),
        );
    }
}
