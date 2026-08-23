<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Subscriber;

use DateTimeImmutable;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Subscriber\ReuseToggleSubscriber;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ReuseToggleSubscriberTest extends IntegrationTestCase
{
    public function tearDown(): void
    {
        $this->repository()->purgeAll();

        parent::tearDown();
    }

    #[Test]
    public function subscriberIsRegisteredForSettingChangedEvent(): void
    {
        $dispatcher = $this->get(EventDispatcherInterface::class);

        $registered = false;
        foreach ($dispatcher->getListeners(SettingChangedEvent::class) as $listener) {
            if (is_array($listener) && $listener[0] instanceof ReuseToggleSubscriber) {
                $registered = true;
            }
        }

        $this->assertTrue($registered, 'ReuseToggleSubscriber must listen to SettingChangedEvent.');
    }

    #[Test]
    public function flippingReusePreventionOffPurgesEveryUsersHistory(): void
    {
        $userA = $this->seedEntry();
        $userB = $this->seedEntry();

        $this->subscriber($this->disabledSettings())->onSettingChanged($this->reuseOffEvent());

        $this->assertSame([], $this->repository()->findRecentHashes($userA, 10));
        $this->assertSame([], $this->repository()->findRecentHashes($userB, 10));
    }

    #[Test]
    public function reuseStillEnabledDoesNotPurgeHistory(): void
    {
        $userA = $this->seedEntry();

        $this->subscriber($this->enabledSettings())->onSettingChanged($this->reuseOffEvent());

        $this->assertSame(
            ['a-hash'],
            $this->repository()->findRecentHashes($userA, 10),
            'History must survive while reuse prevention is still enabled (BR003).',
        );
    }

    #[Test]
    public function aFailingPurgeIsSwallowedSoTheToggleWriteSurvives(): void
    {
        $seededUser = $this->seedEntry();

        $throwingRepository = $this->createStub(PasswordHistoryRepositoryInterface::class);
        $throwingRepository->method('purgeAll')->willThrowException(new RuntimeException('table outage'));

        $subscriber = new ReuseToggleSubscriber(
            $this->disabledSettings(),
            $throwingRepository,
            $this->get(LoggerInterface::class),
        );

        $completedGracefully = false;
        $subscriber->onSettingChanged($this->reuseOffEvent());
        $completedGracefully = true;

        $this->assertTrue(
            $completedGracefully,
            'A purge failure must not propagate out of the subscriber (BR008/BR017).',
        );
        $this->assertSame(
            ['a-hash'],
            $this->repository()->findRecentHashes($seededUser, 10),
            'The isolated purge failure must leave the real table untouched.',
        );
    }

    private function reuseOffEvent(): SettingChangedEvent
    {
        return new SettingChangedEvent(ModuleSettingsService::REUSE_PREVENTION_ENABLE, 1, Module::MODULE_ID);
    }

    private function subscriber(ModuleSettingsServiceInterface $settings): ReuseToggleSubscriber
    {
        return new ReuseToggleSubscriber(
            $settings,
            $this->repository(),
            $this->get(LoggerInterface::class),
        );
    }

    private function disabledSettings(): ModuleSettingsServiceInterface
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn(false);

        return $settings;
    }

    private function enabledSettings(): ModuleSettingsServiceInterface
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn(true);

        return $settings;
    }

    private function seedEntry(): string
    {
        $userId = substr(uniqid('pwh', true), 0, 32);
        $this->repository()->append($userId, 'a-hash', new DateTimeImmutable('2026-01-01 10:00:00'));

        return $userId;
    }

    private function repository(): PasswordHistoryRepositoryInterface
    {
        return $this->get(PasswordHistoryRepositoryInterface::class);
    }
}
