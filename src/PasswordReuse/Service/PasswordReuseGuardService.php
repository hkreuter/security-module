<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use Psr\Log\LoggerInterface;

class PasswordReuseGuardService implements PasswordReuseGuardServiceInterface
{
    public function __construct(
        private PasswordCollectionServiceInterface $collectionService,
        private ModuleSettingsServiceInterface $settings,
        private ConfirmedChangeRegistryInterface $changeRegistry,
        private LoggerInterface $logger,
    ) {
    }

    public function guardChange(
        string $userId,
        #[\SensitiveParameter] string $candidate,
        string $currentHash,
    ): void {
        if (!$this->settings->isReusePreventionEnabled()) {
            return;
        }

        if ($currentHash === '') {
            return;
        }

        if ($this->isReused($userId, $candidate, $currentHash)) {
            throw new PasswordReuseException();
        }

        $this->changeRegistry->confirm($userId);
    }

    private function isReused(
        string $userId,
        #[\SensitiveParameter] string $candidate,
        string $currentHash,
    ): bool {
        try {
            return $this->collectionService->isCandidateInCollection($userId, $candidate, $currentHash);
        } catch (PasswordReuseCheckException $exception) {
            $this->logger->error(
                'Password reuse check could not be completed; rejecting the change (fail-closed).',
                ['exception' => $exception]
            );
            throw $exception;
        }
    }
}
