<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Settings;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordReuseSettingsMetadataTest extends IntegrationTestCase
{
    private const GROUP = 'password_reuse';

    #[Test]
    public function reusePreventionToggleIsRegisteredAsBooleanDefaultingOff(): void
    {
        $setting = $this->getSetting(ModuleSettingsService::REUSE_PREVENTION_ENABLE);

        $this->assertSame('bool', $setting->getType());
        $this->assertFalse($setting->getValue());
        $this->assertSame(self::GROUP, $setting->getGroupName());
    }

    #[Test]
    public function changeNotificationToggleIsRegisteredAsBooleanDefaultingOff(): void
    {
        $setting = $this->getSetting(ModuleSettingsService::CHANGE_NOTIFICATION_ENABLE);

        $this->assertSame('bool', $setting->getType());
        $this->assertFalse($setting->getValue());
        $this->assertSame(self::GROUP, $setting->getGroupName());
    }

    #[Test]
    public function customerCollectionSizeIsSelectOfThreeFiveTenDefaultingToFive(): void
    {
        $setting = $this->getSetting(ModuleSettingsService::CUSTOMER_COLLECTION_SIZE);

        $this->assertSame('select', $setting->getType());
        $this->assertSame('5', $setting->getValue());
        $this->assertSame(['3', '5', '10'], $setting->getConstraints());
        $this->assertSame(self::GROUP, $setting->getGroupName());
    }

    #[Test]
    public function adminCollectionSizeIsSelectOfFiveTenTwentyFourDefaultingToTen(): void
    {
        $setting = $this->getSetting(ModuleSettingsService::ADMIN_COLLECTION_SIZE);

        $this->assertSame('select', $setting->getType());
        $this->assertSame('10', $setting->getValue());
        $this->assertSame(['5', '10', '24'], $setting->getConstraints());
        $this->assertSame(self::GROUP, $setting->getGroupName());
    }

    private function getSetting(string $name): Setting
    {
        $moduleConfiguration = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleConfigurationDaoBridgeInterface::class)
            ->get(Module::MODULE_ID);

        $this->assertTrue(
            $moduleConfiguration->hasModuleSetting($name),
            "Module setting \"$name\" is not registered in metadata.php."
        );

        return $moduleConfiguration->getModuleSetting($name);
    }
}
