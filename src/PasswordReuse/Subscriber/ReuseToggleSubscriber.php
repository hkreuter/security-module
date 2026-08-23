<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Subscriber;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class ReuseToggleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ModuleSettingsServiceInterface $settings,
        private readonly PasswordHistoryRepositoryInterface $historyRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SettingChangedEvent::class => 'onSettingChanged',
        ];
    }

    public function onSettingChanged(SettingChangedEvent $event): void
    {
        if (!$this->isReuseToggle($event)) {
            return;
        }

        if ($this->settings->isReusePreventionEnabled()) {
            return;
        }

        $this->purgeHistory();
    }

    private function isReuseToggle(SettingChangedEvent $event): bool
    {
        return $event->getModuleId() === Module::MODULE_ID
            && $event->getSettingName() === ModuleSettingsService::REUSE_PREVENTION_ENABLE;
    }

    private function purgeHistory(): void
    {
        try {
            $this->historyRepository->purgeAll();
        } catch (Throwable $throwable) {
            $this->logger->error(
                'Failed to purge password history after disabling reuse prevention.',
                ['exception' => $throwable]
            );
        }
    }
}
