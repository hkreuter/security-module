<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\UnicodeString;

class ModuleSettingsServiceTest extends TestCase
{
    #[Test]
    public function isReusePreventionEnabledReturnsTrueWhenEnabled(): void
    {
        $sut = $this->getSut(booleanSettings: [ModuleSettingsService::REUSE_PREVENTION_ENABLE => true]);

        $this->assertTrue($sut->isReusePreventionEnabled());
    }

    #[Test]
    public function isReusePreventionEnabledReturnsFalseWhenDisabled(): void
    {
        $sut = $this->getSut(booleanSettings: [ModuleSettingsService::REUSE_PREVENTION_ENABLE => false]);

        $this->assertFalse($sut->isReusePreventionEnabled());
    }

    #[Test]
    public function isChangeNotificationEnabledReturnsTrueWhenEnabled(): void
    {
        $sut = $this->getSut(booleanSettings: [ModuleSettingsService::CHANGE_NOTIFICATION_ENABLE => true]);

        $this->assertTrue($sut->isChangeNotificationEnabled());
    }

    #[Test]
    public function isChangeNotificationEnabledReturnsFalseWhenDisabled(): void
    {
        $sut = $this->getSut(booleanSettings: [ModuleSettingsService::CHANGE_NOTIFICATION_ENABLE => false]);

        $this->assertFalse($sut->isChangeNotificationEnabled());
    }

    #[Test]
    public function getCustomerCollectionSizeReturnsPersistedValueAsInteger(): void
    {
        $size = $this->randomCustomerSize();

        $sut = $this->getSut(stringSettings: [ModuleSettingsService::CUSTOMER_COLLECTION_SIZE => (string) $size]);

        $this->assertSame($size, $sut->getCustomerCollectionSize());
    }

    #[Test]
    public function getAdminCollectionSizeReturnsPersistedValueAsInteger(): void
    {
        $size = $this->randomAdminSize();

        $sut = $this->getSut(stringSettings: [ModuleSettingsService::ADMIN_COLLECTION_SIZE => (string) $size]);

        $this->assertSame($size, $sut->getAdminCollectionSize());
    }

    #[Test]
    #[DataProvider('customerRightsProvider')]
    public function resolveCollectionSizeForRightsReturnsCustomerSizeForCustomerRights(?string $rights): void
    {
        $customerSize = 5;
        $adminSize = 24;

        $sut = $this->getSut(stringSettings: [
            ModuleSettingsService::CUSTOMER_COLLECTION_SIZE => (string) $customerSize,
            ModuleSettingsService::ADMIN_COLLECTION_SIZE => (string) $adminSize,
        ]);

        $this->assertSame($customerSize, $sut->resolveCollectionSizeForRights($rights));
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function customerRightsProvider(): array
    {
        return [
            'user rights' => ['user'],
            'empty rights' => [''],
            'null rights' => [null],
        ];
    }

    #[Test]
    #[DataProvider('adminRightsProvider')]
    public function resolveCollectionSizeForRightsReturnsAdminSizeForAdminRights(string $rights): void
    {
        $customerSize = 5;
        $adminSize = 24;

        $sut = $this->getSut(stringSettings: [
            ModuleSettingsService::CUSTOMER_COLLECTION_SIZE => (string) $customerSize,
            ModuleSettingsService::ADMIN_COLLECTION_SIZE => (string) $adminSize,
        ]);

        $this->assertSame($adminSize, $sut->resolveCollectionSizeForRights($rights));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminRightsProvider(): array
    {
        return [
            'malladmin' => ['malladmin'],
            'subshop id' => ['1'],
            'unknown backend rights' => ['some_backend_role'],
        ];
    }

    #[Test]
    public function resolveCollectionSizeClampsAdminSizeUpToCustomerSizeWhenConfiguredLower(): void
    {
        $customerSize = 10;
        $adminSize = 5;

        $sut = $this->getSut(stringSettings: [
            ModuleSettingsService::CUSTOMER_COLLECTION_SIZE => (string) $customerSize,
            ModuleSettingsService::ADMIN_COLLECTION_SIZE => (string) $adminSize,
        ]);

        $this->assertSame($customerSize, $sut->resolveCollectionSizeForRights('malladmin'));
    }

    #[Test]
    public function resolveCollectionSizeReturnsAdminSizeWhenEqualToCustomerSize(): void
    {
        $size = 10;

        $sut = $this->getSut(stringSettings: [
            ModuleSettingsService::CUSTOMER_COLLECTION_SIZE => (string) $size,
            ModuleSettingsService::ADMIN_COLLECTION_SIZE => (string) $size,
        ]);

        $this->assertSame($size, $sut->resolveCollectionSizeForRights('malladmin'));
    }

    #[Test]
    public function saveReusePreventionEnabledDelegatesToModuleSettingService(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService->expects($this->once())
            ->method('saveBoolean')
            ->with(ModuleSettingsService::REUSE_PREVENTION_ENABLE, true, Module::MODULE_ID);

        $this->getSut(moduleSettingService: $moduleSettingService)->saveReusePreventionEnabled(true);
    }

    #[Test]
    public function saveChangeNotificationEnabledDelegatesToModuleSettingService(): void
    {
        $moduleSettingService = $this->createMock(ModuleSettingServiceInterface::class);
        $moduleSettingService->expects($this->once())
            ->method('saveBoolean')
            ->with(ModuleSettingsService::CHANGE_NOTIFICATION_ENABLE, false, Module::MODULE_ID);

        $this->getSut(moduleSettingService: $moduleSettingService)->saveChangeNotificationEnabled(false);
    }

    #[Test]
    public function implementsInterface(): void
    {
        $this->assertInstanceOf(ModuleSettingsServiceInterface::class, $this->getSut());
    }

    private function randomCustomerSize(): int
    {
        return [3, 5, 10][random_int(0, 2)];
    }

    private function randomAdminSize(): int
    {
        return [5, 10, 24][random_int(0, 2)];
    }

    /**
     * @param array<string, bool> $booleanSettings
     * @param array<string, string> $stringSettings
     */
    private function getSut(
        array $booleanSettings = [],
        array $stringSettings = [],
        ?ModuleSettingServiceInterface $moduleSettingService = null,
    ): ModuleSettingsService {
        if ($moduleSettingService === null) {
            $stub = $this->createStub(ModuleSettingServiceInterface::class);
            $stub->method('getBoolean')
                ->willReturnCallback(fn(string $name): bool => $booleanSettings[$name] ?? false);
            $stub->method('getString')
                ->willReturnCallback(
                    fn(string $name): UnicodeString => new UnicodeString($stringSettings[$name] ?? '')
                );
            $moduleSettingService = $stub;
        }

        return new ModuleSettingsService($moduleSettingService);
    }
}
