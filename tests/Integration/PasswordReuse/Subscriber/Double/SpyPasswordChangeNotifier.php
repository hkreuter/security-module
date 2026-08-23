<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Subscriber\Double;

use DateTimeInterface;
use OxidEsales\SecurityModule\PasswordReuse\Notifier\Email\PasswordChangeEmailNotifierInterface;

final class SpyPasswordChangeNotifier implements PasswordChangeEmailNotifierInterface
{
    /** @var list<string> */
    private array $notifiedUserIds = [];

    public function notify(string $affectedUserId, DateTimeInterface $changedAt): void
    {
        $this->notifiedUserIds[] = $affectedUserId;
    }

    public function callCount(): int
    {
        return count($this->notifiedUserIds);
    }

    /** @return list<string> */
    public function notifiedUserIds(): array
    {
        return $this->notifiedUserIds;
    }
}
