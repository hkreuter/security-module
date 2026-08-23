<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Subscriber;

use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\BeforeModelUpdateEvent;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Time\ChangeClockInterface;
use OxidEsales\SecurityModule\PasswordReuse\Notifier\Email\PasswordChangeEmailNotifierInterface;
use OxidEsales\SecurityModule\PasswordReuse\Subscriber\PasswordChangeSubscriber;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PasswordChangeSubscriberWiringTest extends IntegrationTestCase
{
    #[Test]
    public function notifierPortIsWiredBehindItsInterface(): void
    {
        $this->assertInstanceOf(
            PasswordChangeEmailNotifierInterface::class,
            $this->get(PasswordChangeEmailNotifierInterface::class),
        );
    }

    #[Test]
    public function storedPasswordReaderIsWiredBehindItsInterface(): void
    {
        $this->assertInstanceOf(
            StoredPasswordReaderInterface::class,
            $this->get(StoredPasswordReaderInterface::class),
        );
    }

    #[Test]
    public function changeClockIsWiredBehindItsInterface(): void
    {
        $this->assertInstanceOf(
            ChangeClockInterface::class,
            $this->get(ChangeClockInterface::class),
        );
    }

    #[Test]
    public function subscriberIsRegisteredForBothModelUpdateEvents(): void
    {
        $dispatcher = $this->get(EventDispatcherInterface::class);

        $this->assertTrue($this->hasSubscriber($dispatcher, BeforeModelUpdateEvent::class));
        $this->assertTrue($this->hasSubscriber($dispatcher, AfterModelUpdateEvent::class));
    }

    private function hasSubscriber(EventDispatcherInterface $dispatcher, string $eventName): bool
    {
        foreach ($dispatcher->getListeners($eventName) as $listener) {
            if (is_array($listener) && $listener[0] instanceof PasswordChangeSubscriber) {
                return true;
            }
        }

        return false;
    }
}
