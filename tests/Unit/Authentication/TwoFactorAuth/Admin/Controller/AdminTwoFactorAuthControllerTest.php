<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Admin\Controller;

use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Framework\Session\SessionInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Controller\AdminTwoFactorAuthController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service\AdminTwoFaLoginServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\AuthCodeRequestInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AdminTwoFactorAuthControllerTest extends TestCase
{
    #[Test]
    public function renderRedirectsToLoginWhenNoPendingUserInSession(): void
    {
        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => null]);

        $utilsSpy = $this->createMock(Utils::class);
        $utilsSpy->expects($this->once())
            ->method('redirect')
            ->with('index.php?cl=login', true, 302);

        $this->buildSut(session: $sessionStub, utils: $utilsSpy)->render();
    }

    #[Test]
    public function renderAddsResendTemplateParamsWhenServiceIsResendable(): void
    {
        $userId = uniqid('user_');
        $remainingAttempts = 3;
        $cooldownRemaining = 30;

        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => $userId]);

        $twoFAService = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAService->method('getRemainingAttempts')->with($userId)->willReturn($remainingAttempts);
        $twoFAService->method('getCooldownRemaining')->with($userId)->willReturn($cooldownRemaining);

        $sut = $this->buildSut(twoFAService: $twoFAService, session: $sessionStub);

        $template = $sut->render();

        $this->assertSame('@oe_security_module/admin/admin_two_factor_auth', $template);
        $this->assertTrue($sut->getViewData()['resendable'] ?? false);
        $this->assertSame($remainingAttempts, $sut->getViewData()['remainingAttempts'] ?? null);
        $this->assertSame($cooldownRemaining, $sut->getViewData()['resendCooldownRemaining'] ?? null);
    }

    #[Test]
    public function verifyCodeRedirectsToLoginWhenNoPendingUser(): void
    {
        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => null]);

        $utilsSpy = $this->createMock(Utils::class);
        $utilsSpy->expects($this->once())
            ->method('redirect')
            ->with('index.php?cl=login', true, 302);

        $this->buildSut(session: $sessionStub, utils: $utilsSpy)->verifyCode();
    }

    #[Test]
    public function verifyCodeVerifiesCodeAndCompletesLoginOnSuccess(): void
    {
        $userId = uniqid('user_');
        $code = uniqid('code_');

        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => $userId]);

        $authCodeRequestStub = $this->createStub(AuthCodeRequestInterface::class);
        $authCodeRequestStub->method('getCode')->willReturn($code);

        $twoFAServiceSpy = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceSpy->expects($this->once())
            ->method('verify')
            ->with($userId, $code);

        $adminLoginServiceSpy = $this->createMock(AdminTwoFaLoginServiceInterface::class);
        $adminLoginServiceSpy->expects($this->once())
            ->method('completeLogin')
            ->with($userId);

        $this->buildSut(
            twoFAService: $twoFAServiceSpy,
            adminLoginService: $adminLoginServiceSpy,
            session: $sessionStub,
            authCodeRequest: $authCodeRequestStub,
        )->verifyCode();
    }

    #[Test]
    public function verifyCodeAddsErrorAndDoesNotCompleteLoginOnCodeValidationException(): void
    {
        $exception = new InvalidCodeException();
        $userId = uniqid('user_');

        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => $userId]);

        $twoFAServiceStub = $this->createStub(TwoFAServiceInterface::class);
        $twoFAServiceStub->method('verify')->willThrowException($exception);

        $utilsViewSpy = $this->createMock(UtilsView::class);
        $utilsViewSpy->expects($this->once())
            ->method('addErrorToDisplay')
            ->with($exception);

        $adminLoginServiceSpy = $this->createMock(AdminTwoFaLoginServiceInterface::class);
        $adminLoginServiceSpy->expects($this->never())->method('completeLogin');

        $this->buildSut(
            twoFAService: $twoFAServiceStub,
            adminLoginService: $adminLoginServiceSpy,
            session: $sessionStub,
            utilsView: $utilsViewSpy,
        )->verifyCode();
    }

    #[Test]
    public function abandonChallengeDelegatesToAdminLoginServiceWithSessionUserId(): void
    {
        $userId = uniqid('user_');

        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => $userId]);

        $adminLoginServiceSpy = $this->createMock(AdminTwoFaLoginServiceInterface::class);
        $adminLoginServiceSpy->expects($this->once())
            ->method('abandonLogin')
            ->with($userId);

        $this->buildSut(
            adminLoginService: $adminLoginServiceSpy,
            session: $sessionStub,
        )->abandonChallenge();
    }

    #[Test]
    public function abandonChallengePassesNullToAdminLoginServiceWhenNoSessionUser(): void
    {
        $sessionStub = $this->createConfiguredStub(SessionInterface::class, ['get' => null]);

        $adminLoginServiceSpy = $this->createMock(AdminTwoFaLoginServiceInterface::class);
        $adminLoginServiceSpy->expects($this->once())
            ->method('abandonLogin')
            ->with(null);

        $this->buildSut(
            adminLoginService: $adminLoginServiceSpy,
            session: $sessionStub,
        )->abandonChallenge();
    }

    /**
     * Builds a partial mock of AdminTwoFactorAuthController.
     *
     * - setConstructorArgs: passes test doubles via constructor injection.
     * - mocks initParent(): suppresses AdminController::__construct() which
     *   requires the full OXID bootstrap.
     * - mocks addTplParam()/getViewData(): captures template params for assertions.
     */
    private function buildSut(
        TwoFAServiceInterface $twoFAService = null,
        AdminTwoFaLoginServiceInterface $adminLoginService = null,
        SessionInterface $session = null,
        AuthCodeRequestInterface $authCodeRequest = null,
        UtilsView $utilsView = null,
        Utils $utils = null,
    ): AdminTwoFactorAuthController {
        $twoFAService      ??= $this->createStub(TwoFAServiceInterface::class);
        $adminLoginService ??= $this->createStub(AdminTwoFaLoginServiceInterface::class);
        $session           ??= $this->createStub(SessionInterface::class);
        $authCodeRequest   ??= $this->createStub(AuthCodeRequestInterface::class);
        $utilsView         ??= $this->createStub(UtilsView::class);
        $utils             ??= $this->createStub(Utils::class);

        $viewData = [];

        $sut = $this->getMockBuilder(AdminTwoFactorAuthController::class)
            ->setConstructorArgs([$twoFAService, $adminLoginService, $session, $authCodeRequest, $utilsView, $utils])
            ->onlyMethods(['initParent', 'addTplParam', 'getViewData'])
            ->getMock();

        $sut->method('addTplParam')
            ->willReturnCallback(function (string $key, mixed $value) use (&$viewData): void {
                $viewData[$key] = $value;
            });

        $sut->method('getViewData')
            ->willReturnCallback(function () use (&$viewData) {
                return $viewData;
            });

        return $sut;
    }
}
