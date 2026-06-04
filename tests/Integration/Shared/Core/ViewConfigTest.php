<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Shared\Core;

use Generator;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
// phpcs:ignore Generic.Files.LineLength
use OxidEsales\SecurityModule\FormSecurity\Service\ModuleSettingsServiceInterface as FormSecuritySettingsServiceInterface;
use OxidEsales\SecurityModule\Shared\Core\ViewConfig;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[AllowMockObjectsWithoutExpectations]
class ViewConfigTest extends IntegrationTestCase
{
    #[DataProvider('isTwoFAEnabledForShopDataProvider')]
    public function testIsTwoFAEnabledForShop(bool $shopSettingEnabled): void
    {
        $shopSettingsStub = $this->createStub(TwoFAShopSettingsInterface::class);
        $shopSettingsStub->method('isTwoFactorAuthEnabled')->willReturn($shopSettingEnabled);

        $sut = $this->getSut([
            TwoFAShopSettingsInterface::class => $shopSettingsStub,
        ]);

        $this->assertSame($shopSettingEnabled, $sut->isTwoFAEnabledForShop());
    }

    public static function isTwoFAEnabledForShopDataProvider(): Generator
    {
        yield 'shop setting enabled' => ['shopSettingEnabled' => true];
        yield 'shop setting disabled' => ['shopSettingEnabled' => false];
    }

    #[Test]
    #[DataProvider('getSecurityModuleFormSettingsDataProvider')]
    public function getSecurityModuleFormSettingsReturnsFormSecurityService(bool $enabled): void
    {
        $formSettingsStub = $this->createStub(FormSecuritySettingsServiceInterface::class);
        $formSettingsStub->method('isGetFormStripStokenEnabled')->willReturn($enabled);

        $sut = $this->getSut([
            FormSecuritySettingsServiceInterface::class => $formSettingsStub,
        ]);

        $this->assertSame($enabled, $sut->getSecurityModuleFormSettings()->isGetFormStripStokenEnabled());
    }

    public static function getSecurityModuleFormSettingsDataProvider(): \Generator
    {
        yield 'form security enabled' => ['enabled' => true];
        yield 'form security disabled' => ['enabled' => false];
    }

    #[Test]
    public function getHiddenParamsOnlyExcludesStokenAndIncludesLangAndAdditionalParams(): void
    {
        $langHidden = '<input type="hidden" name="lang" value="0">';
        $additionalParams = '<input type="hidden" name="extra" value="1">';

        $sut = $this->getSut();
        $sut->method('getFormLang')->willReturn($langHidden);
        $sut->method('getAdditionalRequestParameters')->willReturn($additionalParams);

        $result = $sut->getHiddenParamsOnly();

        $this->assertStringContainsString($langHidden, $result);
        $this->assertStringContainsString($additionalParams, $result);
        $this->assertStringNotContainsString('stoken', $result);
        $this->assertStringNotContainsString('sid', $result);
    }

    #[Test]
    public function getHiddenParamsOnlyReturnsEmptyStringWhenNoLangAndNoAdditionalParams(): void
    {
        $sut = $this->getSut();
        $sut->method('getFormLang')->willReturn('');
        $sut->method('getAdditionalRequestParameters')->willReturn('');

        $this->assertSame('', $sut->getHiddenParamsOnly());
    }

    private function getSut(array $serviceOverrides = []): ViewConfig
    {
        /** @var ViewConfig $sut */
        $sut = $this->getMockBuilder(ViewConfig::class)
            ->onlyMethods(['getService', 'getFormLang', 'getAdditionalRequestParameters'])
            ->getMock();
        $sut->method('getService')->willReturnCallback(
            fn(string $id) => $serviceOverrides[$id] ?? ContainerFacade::get($id)
        );

        return $sut;
    }
}
