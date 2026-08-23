<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Subscriber;

use DateTimeImmutable;
use DateTimeInterface;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\BeforeModelUpdateEvent;
use OxidEsales\SecurityModule\PasswordReuse\DTO\AccountDataInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Time\ChangeClockInterface;
use OxidEsales\SecurityModule\PasswordReuse\Notifier\Email\PasswordChangeEmailNotifierInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordHistoryServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Subscriber\PasswordChangeSubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PasswordChangeSubscriberTest extends TestCase
{
    private const USER_ID = 'user-42';
    private const OLD_HASH = 'old-hash';
    private const NEW_HASH = 'new-hash';

    #[Test]
    public function afterUpdateWithoutPriorArmingDoesNothing(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->never())->method('notify');

        $sut = $this->getSut(history: $history, notifier: $notifier);
        $sut->handleAfterUpdate($this->afterEvent(self::NEW_HASH));
    }

    #[Test]
    public function unchangedPasswordValueDoesNothing(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->never())->method('notify');

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: true),
            reader: $this->reader(self::OLD_HASH),
            history: $history,
            notifier: $notifier,
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::OLD_HASH);
    }

    #[Test]
    public function emptyOldHashIsInitialEstablishmentAndDoesNothing(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->never())->method('notify');

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: true),
            reader: $this->reader(''),
            history: $history,
            notifier: $notifier,
        );

        $this->fire($sut, oldStored: '', persisted: self::NEW_HASH);
    }

    #[Test]
    public function genuineChangeWithReuseOnRecordsSupersededHash(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->once())
            ->method('record')
            ->with(
                self::USER_ID,
                self::OLD_HASH,
                'malladmin',
                $this->isInstanceOf(DateTimeInterface::class),
            );

        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->never())->method('notify');

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: false),
            reader: $this->reader(self::OLD_HASH),
            history: $history,
            notifier: $notifier,
            resolver: $this->resolver('malladmin'),
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function genuineChangeWithNotifyOnNotifies(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');

        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->once())
            ->method('notify')
            ->with(self::USER_ID, $this->isInstanceOf(DateTimeInterface::class));

        $sut = $this->getSut(
            settings: $this->settings(reuse: false, notify: true),
            reader: $this->reader(self::OLD_HASH),
            history: $history,
            notifier: $notifier,
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function recordAndNotifyAreIndependentlyGated(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->once())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->once())->method('notify');

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: true),
            reader: $this->reader(self::OLD_HASH),
            history: $history,
            notifier: $notifier,
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function legacyRehashOldHashIsSuppressedWhenNotConfirmed(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->never())->method('notify');

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->method('isConfirmed')->willReturn(false);
        $registry->expects($this->once())->method('clear')->with(self::USER_ID);

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: true),
            reader: $this->reader(self::OLD_HASH),
            history: $history,
            notifier: $notifier,
            registry: $registry,
            bridge: $this->bridge(needsRehash: true),
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function legacyRehashOldHashProceedsWhenConfirmedThenClears(): void
    {
        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->once())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->once())->method('notify');

        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->method('isConfirmed')->with(self::USER_ID)->willReturn(true);
        $registry->expects($this->once())->method('clear')->with(self::USER_ID);

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: true),
            reader: $this->reader(self::OLD_HASH),
            history: $history,
            notifier: $notifier,
            registry: $registry,
            bridge: $this->bridge(needsRehash: true),
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function recordFailureIsLoggedAndSwallowed(): void
    {
        $history = $this->createStub(PasswordHistoryServiceInterface::class);
        $history->method('record')->willThrowException(new RuntimeException('db down'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: true),
            reader: $this->reader(self::OLD_HASH),
            history: $history,
            logger: $logger,
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function notifyFailureIsLoggedAndSwallowed(): void
    {
        $notifier = $this->createStub(PasswordChangeEmailNotifierInterface::class);
        $notifier->method('notify')->willThrowException(new RuntimeException('mail down'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $sut = $this->getSut(
            settings: $this->settings(reuse: false, notify: true),
            reader: $this->reader(self::OLD_HASH),
            notifier: $notifier,
            logger: $logger,
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function beforeUpdateDoesNotArmWhenBothTogglesOff(): void
    {
        $reader = $this->createMock(StoredPasswordReaderInterface::class);
        $reader->expects($this->never())->method('getStoredPasswordHash');

        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');

        $sut = $this->getSut(
            settings: $this->settings(reuse: false, notify: false),
            reader: $reader,
            history: $history,
        );

        $this->fire($sut, oldStored: self::OLD_HASH, persisted: self::NEW_HASH);
    }

    #[Test]
    public function beforeUpdateSwallowsThrowingToggleReadAndDoesNotArm(): void
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willThrowException(new RuntimeException('settings down'));

        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->never())->method('notify');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $sut = $this->getSut(
            settings: $settings,
            history: $history,
            notifier: $notifier,
            logger: $logger,
        );

        $sut->handleBeforeUpdate($this->beforeEvent());
        $sut->handleAfterUpdate($this->afterEvent(self::NEW_HASH));
    }

    #[Test]
    public function beforeUpdateSwallowsThrowingStoredHashReadAndDoesNotArm(): void
    {
        $reader = $this->createStub(StoredPasswordReaderInterface::class);
        $reader->method('getStoredPasswordHash')->willThrowException(new RuntimeException('reader down'));

        $history = $this->createMock(PasswordHistoryServiceInterface::class);
        $history->expects($this->never())->method('record');
        $notifier = $this->createMock(PasswordChangeEmailNotifierInterface::class);
        $notifier->expects($this->never())->method('notify');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $sut = $this->getSut(
            settings: $this->settings(reuse: true, notify: true),
            history: $history,
            notifier: $notifier,
            reader: $reader,
            logger: $logger,
        );

        $sut->handleBeforeUpdate($this->beforeEvent());
        $sut->handleAfterUpdate($this->afterEvent(self::NEW_HASH));
    }

    private function fire(PasswordChangeSubscriber $sut, string $oldStored, string $persisted): void
    {
        $sut->handleBeforeUpdate($this->beforeEvent());
        $sut->handleAfterUpdate($this->afterEvent($persisted));
    }

    private function beforeEvent(): BeforeModelUpdateEvent
    {
        return new BeforeModelUpdateEvent($this->userModel(self::NEW_HASH));
    }

    private function afterEvent(string $persistedHash): AfterModelUpdateEvent
    {
        return new AfterModelUpdateEvent($this->userModel($persistedHash));
    }

    private function userModel(string $passwordHash): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(self::USER_ID);
        $user->method('getFieldData')->with('oxpassword')->willReturn($passwordHash);

        return $user;
    }

    private function settings(bool $reuse, bool $notify): ModuleSettingsServiceInterface
    {
        $settings = $this->createStub(ModuleSettingsServiceInterface::class);
        $settings->method('isReusePreventionEnabled')->willReturn($reuse);
        $settings->method('isChangeNotificationEnabled')->willReturn($notify);

        return $settings;
    }

    private function reader(?string $hash): StoredPasswordReaderInterface
    {
        $reader = $this->createStub(StoredPasswordReaderInterface::class);
        $reader->method('getStoredPasswordHash')->willReturn($hash);

        return $reader;
    }

    private function resolver(string $rights): AccountTypeResolverInterface
    {
        $account = $this->createStub(AccountDataInterface::class);
        $account->method('getRights')->willReturn($rights);

        $resolver = $this->createStub(AccountTypeResolverInterface::class);
        $resolver->method('resolveAccount')->willReturn($account);

        return $resolver;
    }

    private function bridge(bool $needsRehash): PasswordServiceBridgeInterface
    {
        $bridge = $this->createStub(PasswordServiceBridgeInterface::class);
        $bridge->method('passwordNeedsRehash')->willReturn($needsRehash);

        return $bridge;
    }

    private function getSut(
        ?ModuleSettingsServiceInterface $settings = null,
        ?ConfirmedChangeRegistryInterface $registry = null,
        ?PasswordHistoryServiceInterface $history = null,
        ?PasswordChangeEmailNotifierInterface $notifier = null,
        ?AccountTypeResolverInterface $resolver = null,
        ?PasswordServiceBridgeInterface $bridge = null,
        ?StoredPasswordReaderInterface $reader = null,
        ?LoggerInterface $logger = null,
    ): PasswordChangeSubscriber {
        return new PasswordChangeSubscriber(
            $settings ?? $this->settings(reuse: true, notify: true),
            $registry ?? $this->createStub(ConfirmedChangeRegistryInterface::class),
            $history ?? $this->createStub(PasswordHistoryServiceInterface::class),
            $notifier ?? $this->createStub(PasswordChangeEmailNotifierInterface::class),
            $resolver ?? $this->resolver('user'),
            $bridge ?? $this->bridge(needsRehash: false),
            $reader ?? $this->reader(self::OLD_HASH),
            $this->clock(),
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }

    private function clock(): ChangeClockInterface
    {
        $clock = $this->createStub(ChangeClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-08-21 10:00:00'));

        return $clock;
    }
}
