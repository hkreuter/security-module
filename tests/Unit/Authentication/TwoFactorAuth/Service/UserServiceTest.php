<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\UserService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\UserServiceInterface;
use OxidEsales\SecurityModule\Shared\Model\User2FAInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserService::class)]
final class UserServiceTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $sut = new UserService(
            $this->createMock(ModuleSettingsServiceInterface::class),
        );

        $this->assertInstanceOf(UserServiceInterface::class, $sut);
    }

    public function testRequires2FAWhenGlobalEnabledAndUserEnabled(): void
    {
        $sut = new UserService($this->createSettings(globalEnabled: true));

        $this->assertTrue($sut->requiresTwoFactorAuth($this->createUser(enabled: true)));
    }

    public function testDoesNotRequire2FAWhenGlobalDisabled(): void
    {
        $sut = new UserService($this->createSettings(globalEnabled: false));

        $this->assertFalse($sut->requiresTwoFactorAuth($this->createUser(enabled: true)));
    }

    public function testDoesNotRequire2FAWhenUserDisabled(): void
    {
        $sut = new UserService($this->createSettings(globalEnabled: true));

        $this->assertFalse($sut->requiresTwoFactorAuth($this->createUser(enabled: false)));
    }

    public function testDoesNotRequire2FAWhenBothDisabled(): void
    {
        $sut = new UserService($this->createSettings(globalEnabled: false));

        $this->assertFalse($sut->requiresTwoFactorAuth($this->createUser(enabled: false)));
    }

    private function createSettings(bool $globalEnabled): ModuleSettingsServiceInterface
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->method('isTwoFactorAuthEnabled')->willReturn($globalEnabled);

        return $settings;
    }

    private function createUser(bool $enabled): User2FAInterface
    {
        $user = $this->createMock(User2FAInterface::class);
        $user->method('is2FAEnabled')->willReturn($enabled);

        return $user;
    }
}
