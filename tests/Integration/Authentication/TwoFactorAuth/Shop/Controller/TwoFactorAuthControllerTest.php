<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Authentication\TwoFactorAuth\Shop\Controller;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Shop\Controller\TwoFactorAuthController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAUserServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\AuthCodeRequestInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\JsonResponseInterface;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Generator\MockClass;
use PHPUnit\Framework\MockObject\MockObject;

class TwoFactorAuthControllerTest extends IntegrationTestCase
{
    #[Test]
    public function renderDoesNotSetResendableWhenServiceIsNotResendable(): void
    {
        $sut = $this->getSut(
            twoFAService: $this->createStub(TwoFAServiceInterface::class),
        );
        $sut->render();

        $this->assertNull($sut->getViewDataElement('resendable'));
    }

    #[Test]
    public function renderSetsResendableTrueAndParamsFromServiceWhenResendable(): void
    {
        $userId = uniqid();

        $twoFAUserServiceStub = $this->createStub(TwoFAUserServiceInterface::class);
        $twoFAUserServiceStub->method('getPendingUserId')->willReturn($userId);

        /** @var TwoFAServiceInterface&TwoFAResendableInterface&MockObject $twoFAServiceMock */
        $twoFAServiceMock = $this->createMockForIntersectionOfInterfaces([
            TwoFAServiceInterface::class,
            TwoFAResendableInterface::class,
        ]);
        $twoFAServiceMock->method('getRemainingAttempts')->with($userId)->willReturn($remaining = random_int(1, 5));
        $twoFAServiceMock->method('getCooldownRemaining')->with($userId)->willReturn($cooldown = random_int(0, 30));

        $sut = $this->getSut(
            twoFAService: $twoFAServiceMock,
            twoFAUserService: $twoFAUserServiceStub,
        );
        $sut->render();

        $this->assertTrue($sut->getViewDataElement('resendable'));
        $this->assertSame($remaining, $sut->getViewDataElement('remainingAttempts'));
        $this->assertSame($cooldown, $sut->getViewDataElement('resendCooldownRemaining'));
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
