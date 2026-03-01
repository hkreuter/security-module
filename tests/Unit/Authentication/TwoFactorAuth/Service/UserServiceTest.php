<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFactorAuthOrchestratorInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private const USER_ID = 'test-user-id';

    public function testIsTwoFactorRequiredReturnsFalseWhenGloballyDisabled(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('isTwoFactorAuthEnabled')->willReturn(false);

        $sut = new UserService(
            $this->createStub(TwoFactorAuthOrchestratorInterface::class),
            $settings,
            false,
        );

        $this->assertFalse($sut->isTwoFactorRequired(self::USER_ID, false));
    }

    public function testIsTwoFactorRequiredForAdminWhenMandatory(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('isTwoFactorAuthEnabled')->willReturn(false);

        $sut = new UserService(
            $this->createStub(TwoFactorAuthOrchestratorInterface::class),
            $settings,
            true,
        );

        $this->assertTrue($sut->isTwoFactorRequired(self::USER_ID, true));
    }

    public function testIsTwoFactorRequiredForAdminWhenNotMandatory(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('isTwoFactorAuthEnabled')->willReturn(false);

        $sut = new UserService(
            $this->createStub(TwoFactorAuthOrchestratorInterface::class),
            $settings,
            false,
        );

        $this->assertFalse($sut->isTwoFactorRequired(self::USER_ID, true));
    }

    public function testInitiateTwoFactorDelegatesToOrchestrator(): void
    {
        $orchestrator = $this->createMock(TwoFactorAuthOrchestratorInterface::class);
        $orchestrator->expects($this->once())
            ->method('initiate')
            ->with(self::USER_ID, 'sid123', 'frontend');

        $settings = $this->createStub(ModuleSettingsServiceInterface::class);

        $sut = new UserService($orchestrator, $settings, false);

        $sut->initiateTwoFactor(self::USER_ID, 'sid123', 'frontend');
    }
}
