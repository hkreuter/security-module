<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing\PasswordHasherInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use Throwable;

class PasswordCollectionService implements PasswordCollectionServiceInterface
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private PasswordHistoryRepositoryInterface $historyRepository,
        private ModuleSettingsServiceInterface $settings,
        private AccountTypeResolverInterface $accountTypeResolver,
    ) {
    }

    public function isCandidateInCollection(
        string $userId,
        #[\SensitiveParameter] string $candidate,
        string $currentHash,
    ): bool {
        try {
            foreach ($this->buildCollection($userId, $currentHash) as $member) {
                if ($this->passwordHasher->verifyPassword($candidate, $member)) {
                    return true;
                }
            }

            return false;
        } catch (PasswordReuseCheckException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new PasswordReuseCheckException($exception);
        }
    }

    /**
     * @return list<string>
     */
    private function buildCollection(string $userId, string $currentHash): array
    {
        $previousEntriesBound = $this->resolveCollectionSize($userId) - 1;
        $previousHashes = $this->historyRepository->findRecentHashes($userId, $previousEntriesBound);

        return [$currentHash, ...$previousHashes];
    }

    private function resolveCollectionSize(string $userId): int
    {
        $rights = $this->accountTypeResolver->resolveAccount($userId)->getRights();

        return $this->settings->resolveCollectionSizeForRights($rights);
    }
}
