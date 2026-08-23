<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository;

use DateTimeInterface;

interface PasswordHistoryRepositoryInterface
{
    public function append(string $userId, string $hash, DateTimeInterface $supersededAt): void;

    /**
     * @return list<string>
     */
    public function findRecentHashes(string $userId, int $limit): array;

    public function deleteSurplusBeyond(string $userId, int $keep): void;

    public function purgeForUser(string $userId): void;

    public function purgeAll(): void;

    public function countForUser(string $userId): int;
}
