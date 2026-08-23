<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use DateTimeInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class PasswordHistoryService implements PasswordHistoryServiceInterface
{
    public function __construct(
        private ModuleSettingsServiceInterface $settings,
        private PasswordHistoryRepositoryInterface $historyRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function record(
        string $userId,
        #[\SensitiveParameter] string $supersededHash,
        ?string $rights,
        DateTimeInterface $supersededAt
    ): void {
        if (!$this->settings->isReusePreventionEnabled()) {
            return;
        }

        $keep = max($this->settings->resolveCollectionSizeForRights($rights) - 1, 0);

        try {
            $this->historyRepository->append($userId, $supersededHash, $supersededAt);
            $this->historyRepository->deleteSurplusBeyond($userId, $keep);
        } catch (Throwable $exception) {
            $this->logger->error(
                'Failed to record superseded password hash.',
                ['exception' => $exception]
            );
        }
    }

    public function purgeForUser(string $userId): void
    {
        $this->historyRepository->purgeForUser($userId);
    }
}
