<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Subscriber;

use DateTimeInterface;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\BeforeModelUpdateEvent;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Time\ChangeClockInterface;
use OxidEsales\SecurityModule\PasswordReuse\Notifier\Email\PasswordChangeEmailNotifierInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordHistoryServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class PasswordChangeSubscriber implements EventSubscriberInterface
{
    /** @var array<string,string> */
    private array $armedOldHash = [];

    public function __construct(
        private readonly ModuleSettingsServiceInterface $settings,
        private readonly ConfirmedChangeRegistryInterface $confirmedChangeRegistry,
        private readonly PasswordHistoryServiceInterface $historyService,
        private readonly PasswordChangeEmailNotifierInterface $notifier,
        private readonly AccountTypeResolverInterface $accountTypeResolver,
        private readonly PasswordServiceBridgeInterface $passwordServiceBridge,
        private readonly StoredPasswordReaderInterface $storedPasswordReader,
        private readonly ChangeClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeModelUpdateEvent::class => 'handleBeforeUpdate',
            AfterModelUpdateEvent::class => 'handleAfterUpdate',
        ];
    }

    public function handleBeforeUpdate(BeforeModelUpdateEvent $event): void
    {
        $model = $event->getModel();
        if (!$model instanceof User) {
            return;
        }

        try {
            if (!$this->settings->isReusePreventionEnabled() && !$this->settings->isChangeNotificationEnabled()) {
                return;
            }

            $userId = (string)$model->getId();
            $this->armedOldHash[$userId] = (string)$this->storedPasswordReader->getStoredPasswordHash($userId);
        } catch (Throwable $throwable) {
            $this->logger->error('Failed to capture the previous password hash.', ['exception' => $throwable]);
        }
    }

    public function handleAfterUpdate(AfterModelUpdateEvent $event): void
    {
        $model = $event->getModel();
        if (!$model instanceof User) {
            return;
        }

        $userId = (string)$model->getId();
        if (!array_key_exists($userId, $this->armedOldHash)) {
            return;
        }

        $oldHash = $this->armedOldHash[$userId];
        unset($this->armedOldHash[$userId]);

        try {
            $this->handleGenuineChange($userId, $oldHash, (string)$model->getFieldData('oxpassword'));
        } catch (Throwable $throwable) {
            $this->logger->error('Failed to record or notify a password change.', ['exception' => $throwable]);
        }
    }

    private function handleGenuineChange(string $userId, string $oldHash, string $newHash): void
    {
        if ($newHash === $oldHash || $oldHash === '') {
            return;
        }

        try {
            if ($this->isSuppressedLoginRehash($userId, $oldHash)) {
                return;
            }

            $changedAt = $this->clock->now();
            $this->record($userId, $oldHash, $changedAt);
            $this->sendNotification($userId, $changedAt);
        } finally {
            $this->confirmedChangeRegistry->clear($userId);
        }
    }

    private function isSuppressedLoginRehash(string $userId, string $oldHash): bool
    {
        return $this->passwordServiceBridge->passwordNeedsRehash($oldHash)
            && !$this->confirmedChangeRegistry->isConfirmed($userId);
    }

    private function record(string $userId, string $oldHash, DateTimeInterface $changedAt): void
    {
        if (!$this->settings->isReusePreventionEnabled()) {
            return;
        }

        $rights = $this->accountTypeResolver->resolveAccount($userId)->getRights();
        $this->historyService->record($userId, $oldHash, $rights, $changedAt);
    }

    private function sendNotification(string $userId, DateTimeInterface $changedAt): void
    {
        if (!$this->settings->isChangeNotificationEnabled()) {
            return;
        }

        $this->notifier->notify($userId, $changedAt);
    }
}
