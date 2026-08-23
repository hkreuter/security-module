<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Subscriber;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Subscriber\ReuseToggleSubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ReuseToggleSubscriberTest extends TestCase
{
    private const OTHER_MODULE = 'some_other_module';
    private const OTHER_SETTING = 'oeSecuritySomethingElse';

    #[Test]
    public function subscribesToSettingChangedEvent(): void
    {
        $this->assertArrayHasKey(
            SettingChangedEvent::class,
            ReuseToggleSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function disablingReusePreventionPurgesAllHistory(): void
    {
        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->once())->method('purgeAll');

        $sut = $this->getSut(
            settings: $this->settings(enabled: false),
            repository: $repository,
        );

        $sut->onSettingChanged($this->reuseEvent());
    }

    #[Test]
    public function enablingReusePreventionDoesNotPurge(): void
    {
        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->never())->method('purgeAll');

        $sut = $this->getSut(
            settings: $this->settings(enabled: true),
            repository: $repository,
        );

        $sut->onSettingChanged($this->reuseEvent());
    }

    #[Test]
    public function unrelatedSettingOfOurModuleIsIgnored(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->expects($this->never())->method('isReusePreventionEnabled');
        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->never())->method('purgeAll');

        $sut = $this->getSut(settings: $settings, repository: $repository);

        $sut->onSettingChanged(
            new SettingChangedEvent(self::OTHER_SETTING, 1, Module::MODULE_ID),
        );
    }

    #[Test]
    public function reuseSettingOfAnotherModuleIsIgnored(): void
    {
        $settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $settings->expects($this->never())->method('isReusePreventionEnabled');
        $repository = $this->createMock(PasswordHistoryRepositoryInterface::class);
        $repository->expects($this->never())->method('purgeAll');

        $sut = $this->getSut(settings: $settings, repository: $repository);

        $sut->onSettingChanged(
            new SettingChangedEvent(ModuleSettingsService::REUSE_PREVENTION_ENABLE, 1, self::OTHER_MODULE),
        );
    }

    #[Test]
    public function purgeFailureIsSwallowedAndLogged(): void
    {
        $repository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $repository->method('purgeAll')->willThrowException(new RuntimeException('table down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $sut = $this->getSut(
            settings: $this->settings(enabled: false),
            repository: $repository,
            logger: $logger,
        );

        $sut->onSettingChanged($this->reuseEvent());
    }

    private function reuseEvent(): SettingChangedEvent
    {
        return new SettingChangedEvent(ModuleSettingsService::REUSE_PREVENTION_ENABLE, 1, Module::MODULE_ID);
    }

    private function settings(bool $enabled): ModuleSettingsServiceInterface
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn($enabled);

        return $settings;
    }

    private function getSut(
        ?ModuleSettingsServiceInterface $settings = null,
        ?PasswordHistoryRepositoryInterface $repository = null,
        ?LoggerInterface $logger = null,
    ): ReuseToggleSubscriber {
        return new ReuseToggleSubscriber(
            $settings ?? $this->createStub(ModuleSettingsServiceInterface::class),
            $repository ?? $this->createStub(PasswordHistoryRepositoryInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
