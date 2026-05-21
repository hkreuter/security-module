<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\FormSecurity\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\FormSecurity\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\FormSecurity\Service\ModuleSettingsServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleSettingsServiceTest extends TestCase
{
    #[Test]
    #[DataProvider('gettersDataProvider')]
    public function testGetters(string $method, string $key, bool $mockValue): void
    {
        $settingService = $this->createMock(ModuleSettingServiceInterface::class);
        $settingService->method('getBoolean')
            ->with($key, Module::MODULE_ID)
            ->willReturn($mockValue);

        $sut = $this->getSut(moduleSettingService: $settingService);

        $this->assertSame($mockValue, $sut->$method());
    }

    public static function gettersDataProvider(): array
    {
        return [
            'isGetFormStripStokenEnabled returns true' => [
                'method'    => 'isGetFormStripStokenEnabled',
                'key'       => ModuleSettingsService::GET_FORM_STRIP_STOKEN,
                'mockValue' => true,
            ],
            'isGetFormStripStokenEnabled returns false' => [
                'method'    => 'isGetFormStripStokenEnabled',
                'key'       => ModuleSettingsService::GET_FORM_STRIP_STOKEN,
                'mockValue' => false,
            ],
        ];
    }

    private function getSut(
        ModuleSettingServiceInterface $moduleSettingService = null,
    ): ModuleSettingsServiceInterface {
        return new ModuleSettingsService(
            moduleSettingService: $moduleSettingService
                ?? $this->createStub(ModuleSettingServiceInterface::class),
        );
    }
}
