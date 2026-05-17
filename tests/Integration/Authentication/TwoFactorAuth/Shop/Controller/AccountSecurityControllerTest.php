<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Authentication\TwoFactorAuth\Shop\Controller;

use Generator;
use OxidEsales\Eshop\Application\Controller\AccountController;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Shop\Controller\AccountSecurityController;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAUserSettingsInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\UserSettingsUpdateRequestInterface;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[AllowMockObjectsWithoutExpectations]
class AccountSecurityControllerTest extends IntegrationTestCase
{
    #[Test]
    public function extendsAccountController(): void
    {
        $this->assertInstanceOf(AccountController::class, $this->getSut());
    }

    #[Test]
    #[DataProvider('renderSetsTwoFAEnabledDataProvider')]
    public function renderSetsTwoFAEnabled(bool $userSettingEnabled): void
    {
        $userId = uniqid();

        $userStub = $this->createConfiguredStub(User::class, [
            'getId' => $userId,
            '__get' => new Field('x'),
        ]);

        $userSettingsMock = $this->createMock(TwoFAUserSettingsInterface::class);
        $userSettingsMock->method('isEnabledForUser')->with($userId)->willReturn($userSettingEnabled);

        $sut = $this->getSut(userSettingsService: $userSettingsMock);
        $sut->method('getUser')->willReturn($userStub);
        $sut->render();

        $this->assertSame($userSettingEnabled, $sut->getViewDataElement('twoFAEnabledForUser'));
    }

    public static function renderSetsTwoFAEnabledDataProvider(): Generator
    {
        yield 'user has 2FA enabled' => ['userSettingEnabled' => true];
        yield 'user has 2FA disabled' => ['userSettingEnabled' => false];
    }

    #[Test]
    public function renderDoesNotSetParamsWhenUserIsNull(): void
    {
        $userSettingsSpy = $this->createMock(TwoFAUserSettingsInterface::class);
        $userSettingsSpy->expects($this->never())->method('isEnabledForUser');

        $sut = $this->getSut(userSettingsService: $userSettingsSpy);
        $sut->method('getUser')->willReturn(null);
        $sut->render();

        $this->assertNull($sut->getViewDataElement('twoFAEnabledForUser'));
    }

    #[Test]
    #[DataProvider('saveTwoFactorAuthDataProvider')]
    public function saveTwoFactorAuthPassesRequestValueToUserSettings(bool $twoFAEnabled): void
    {
        $userId = uniqid();

        $userStub = $this->createStub(User::class);
        $userStub->method('getId')->willReturn($userId);

        $updateRequestStub = $this->createStub(UserSettingsUpdateRequestInterface::class);
        $updateRequestStub->method('isTwoFAEnabled')
            ->willReturn($twoFAEnabled);

        $userSettingsSpy = $this->createMock(TwoFAUserSettingsInterface::class);
        $userSettingsSpy->expects($this->once())
            ->method('setEnabledForUser')
            ->with($userId, $twoFAEnabled);

        $sut = $this->getSut(
            userSettingsService: $userSettingsSpy,
            updateRequest: $updateRequestStub,
        );
        $sut->method('getUser')->willReturn($userStub);
        $sut->saveTwoFactorAuth();

        $this->assertTrue($sut->getViewDataElement('twoFASaved'));
    }

    #[Test]
    public function saveTwoFactorAuthDoesNothingWhenUserIsNull(): void
    {
        $userSettingsSpy = $this->createMock(TwoFAUserSettingsInterface::class);
        $userSettingsSpy->expects($this->never())->method('setEnabledForUser');

        $sut = $this->getSut(userSettingsService: $userSettingsSpy);
        $sut->method('getUser')->willReturn(null);
        $sut->saveTwoFactorAuth();

        $this->assertNull($sut->getViewDataElement('twoFASaved'));
    }

    public static function saveTwoFactorAuthDataProvider(): Generator
    {
        yield 'enable 2FA' => ['twoFAEnabled' => true];
        yield 'disable 2FA' => ['twoFAEnabled' => false];
    }

    #[Test]
    #[DataProvider('saveTwoFactorAuthDataProvider')]
    public function saveTwoFactorAuthClearsRememberMeCookieOnlyWhenEnabling(bool $twoFAEnabled): void
    {
        $shopId = random_int(1, 99);

        $userStub = $this->createStub(User::class);
        $userStub->method('getId')->willReturn(uniqid());

        $updateRequestStub = $this->createStub(UserSettingsUpdateRequestInterface::class);
        $updateRequestStub->method('isTwoFAEnabled')->willReturn($twoFAEnabled);

        $contextStub = $this->createStub(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $utilsServerSpy = $this->createMock(UtilsServer::class);
        $utilsServerSpy->expects($twoFAEnabled ? $this->once() : $this->never())
            ->method('deleteUserCookie')
            ->with((string)$shopId);

        $sut = $this->getSut(
            updateRequest: $updateRequestStub,
            utilsServer: $utilsServerSpy,
            context: $contextStub,
        );
        $sut->method('getUser')->willReturn($userStub);

        $sut->saveTwoFactorAuth();
    }

    private function getSut(
        TwoFAUserSettingsInterface $userSettingsService = null,
        UserSettingsUpdateRequestInterface $updateRequest = null,
        UtilsServer $utilsServer = null,
        ContextInterface $context = null,
    ): AccountSecurityController {
        $userSettingsService ??= $this->createStub(TwoFAUserSettingsInterface::class);
        $updateRequest ??= $this->createStub(UserSettingsUpdateRequestInterface::class);
        $utilsServer ??= $this->createStub(UtilsServer::class);
        $context ??= $this->createStub(ContextInterface::class);

        return $this->getMockBuilder(AccountSecurityController::class)
            ->setConstructorArgs([
                'userSettingsService' => $userSettingsService,
                'settingUpdateRequest' => $updateRequest,
                'utilsServer' => $utilsServer,
                'context' => $context,
            ])
            ->onlyMethods(['getUser'])
            ->getMock();
    }
}
