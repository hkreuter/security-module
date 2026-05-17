<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Shop\Controller;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Shop\Controller\TwoFactorAuthController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\AttemptLimitExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\ResendCooldownException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\SessionExpiredException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAUserServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\AuthCodeRequestInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\JsonResponseInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TwoFactorAuthControllerTest extends TestCase
{
    #[Test]
    public function verifyCodeVerifiesCodeAndLoginsUser(): void
    {
        $userId = uniqid();
        $code = uniqid();

        $twoFAUserServiceSpy = $this->createMock(TwoFAUserServiceInterface::class);
        $twoFAUserServiceSpy->method('getPendingUserId')->willReturn($userId);
        $twoFAUserServiceSpy->expects($this->once())
            ->method('loginUser')
            ->with($userId);

        $authCodeRequestStub = $this->createStub(AuthCodeRequestInterface::class);
        $authCodeRequestStub->method('getCode')->willReturn($code);

        $twoFAServiceSpy = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceSpy->expects($this->once())
            ->method('verify')
            ->with($userId, $code);

        $sut = $this->getSut(
            twoFAService: $twoFAServiceSpy,
            twoFAUserService: $twoFAUserServiceSpy,
            authCodeRequest: $authCodeRequestStub,
        );

        $sut->verifyCode();
    }

    #[Test]
    public function verifyCodeDisplaysSessionExpiredErrorWhenNoUserId(): void
    {
        $twoFAUserServiceStub = $this->createStub(TwoFAUserServiceInterface::class);
        $twoFAUserServiceStub->method('getPendingUserId')->willReturn(null);

        $utilsViewSpy = $this->createMock(UtilsView::class);
        $utilsViewSpy->expects($this->once())
            ->method('addErrorToDisplay')
            ->with($this->isInstanceOf(SessionExpiredException::class));

        $sut = $this->getSut(
            twoFAUserService: $twoFAUserServiceStub,
            utilsView: $utilsViewSpy,
        );

        $sut->verifyCode();
    }

    #[Test]
    public function verifyCodeDisplaysErrorOnInvalidCode(): void
    {
        $exception = new InvalidCodeException();

        $twoFAUserServiceStub = $this->createStub(TwoFAUserServiceInterface::class);
        $twoFAUserServiceStub->method('getPendingUserId')->willReturn(uniqid());

        $twoFAServiceStub = $this->createStub(TwoFAServiceInterface::class);
        $twoFAServiceStub->method('verify')->willThrowException($exception);

        $utilsViewSpy = $this->createMock(UtilsView::class);
        $utilsViewSpy->expects($this->once())
            ->method('addErrorToDisplay')
            ->with($exception);

        $sut = $this->getSut(
            twoFAService: $twoFAServiceStub,
            twoFAUserService: $twoFAUserServiceStub,
            utilsView: $utilsViewSpy,
        );

        $sut->verifyCode();
    }

    #[Test]
    public function resendCodeSendsSuccessResponse(): void
    {
        $twoFAUserServiceStub = $this->createStub(TwoFAUserServiceInterface::class);
        $twoFAUserServiceStub->method('getPendingUserId')
            ->willReturn($userId = uniqid());

        $twoFAServiceSpy = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAServiceSpy->expects($this->once())
            ->method('resend')
            ->with($userId);
        $twoFAServiceSpy->method('getRemainingAttempts')
            ->with($userId)
            ->willReturn($remaining = 3);

        $jsonResponseSpy = $this->createMock(JsonResponseInterface::class);
        $jsonResponseSpy->expects($this->once())
            ->method('send')
            ->with(['success' => true, 'remainingAttempts' => $remaining]);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            twoFAUserService: $twoFAUserServiceStub,
            jsonResponse: $jsonResponseSpy,
        )->resendCode();
    }

    #[Test]
    public function resendCodeSends429OnCooldown(): void
    {
        $twoFAUserServiceStub = $this->createStub(TwoFAUserServiceInterface::class);
        $twoFAUserServiceStub->method('getPendingUserId')
            ->willReturn($userId = uniqid());

        $twoFAServiceSpy = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAServiceSpy->expects($this->once())
            ->method('resend')
            ->with($userId)
            ->willThrowException(new ResendCooldownException());

        $jsonResponseSpy = $this->createMock(JsonResponseInterface::class);
        $jsonResponseSpy->expects($this->once())
            ->method('send')
            ->with(['success' => false], 429);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            twoFAUserService: $twoFAUserServiceStub,
            jsonResponse: $jsonResponseSpy,
        )->resendCode();
    }

    #[Test]
    public function resendCodeSends429OnAttemptLimitExceeded(): void
    {
        $twoFAUserServiceStub = $this->createStub(TwoFAUserServiceInterface::class);
        $twoFAUserServiceStub->method('getPendingUserId')
            ->willReturn($userId = uniqid());

        $twoFAServiceSpy = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAServiceSpy->expects($this->once())
            ->method('resend')
            ->with($userId)
            ->willThrowException(new AttemptLimitExceededException());

        $jsonResponseSpy = $this->createMock(JsonResponseInterface::class);
        $jsonResponseSpy->expects($this->once())
            ->method('send')
            ->with(['success' => false], 429);

        $this->getSut(
            twoFAService: $twoFAServiceSpy,
            twoFAUserService: $twoFAUserServiceStub,
            jsonResponse: $jsonResponseSpy,
        )->resendCode();
    }

    #[Test]
    public function abandonChallengeDelegatesToServiceWhenUserIsPending(): void
    {
        $userId = uniqid();

        $twoFAUserServiceSpy = $this->createMock(TwoFAUserServiceInterface::class);
        $twoFAUserServiceSpy->method('getPendingUserId')->willReturn($userId);
        $twoFAUserServiceSpy->expects($this->once())
            ->method('abandonChallenge')
            ->with($userId);

        $this->getSut(twoFAUserService: $twoFAUserServiceSpy)->abandonChallenge();
    }

    #[Test]
    public function abandonChallengeDoesNothingWhenNoPendingUser(): void
    {
        $twoFAUserServiceSpy = $this->createMock(TwoFAUserServiceInterface::class);
        $twoFAUserServiceSpy->method('getPendingUserId')->willReturn(null);
        $twoFAUserServiceSpy->expects($this->never())->method('abandonChallenge');

        $this->getSut(twoFAUserService: $twoFAUserServiceSpy)->abandonChallenge();
    }

    #[Test]
    public function resendCodeSends405WhenServiceIsNotResendable(): void
    {
        $jsonResponseSpy = $this->createMock(JsonResponseInterface::class);
        $jsonResponseSpy->expects($this->once())
            ->method('send')
            ->with(['success' => false], 405);

        $this->getSut(
            twoFAService: $this->createStub(TwoFAServiceInterface::class),
            jsonResponse: $jsonResponseSpy,
        )->resendCode();
    }

    private function getSut(
        TwoFAServiceInterface $twoFAService = null,
        TwoFAUserServiceInterface $twoFAUserService = null,
        AuthCodeRequestInterface $authCodeRequest = null,
        UtilsView $utilsView = null,
        JsonResponseInterface $jsonResponse = null,
        Utils $utils = null,
        Config $config = null,
    ): TwoFactorAuthController {
        return new TwoFactorAuthController(
            twoFAService: $twoFAService ?? $this->createStub(TwoFAServiceInterface::class),
            twoFAUserService: $twoFAUserService ?? $this->createStub(TwoFAUserServiceInterface::class),
            authCodeRequest: $authCodeRequest ?? $this->createStub(AuthCodeRequestInterface::class),
            utilsView: $utilsView ?? $this->createStub(UtilsView::class),
            jsonResponse: $jsonResponse ?? $this->createStub(JsonResponseInterface::class),
            utils: $utils ?? $this->createStub(Utils::class),
            config: $config ?? $this->createStub(Config::class),
        );
    }
}
