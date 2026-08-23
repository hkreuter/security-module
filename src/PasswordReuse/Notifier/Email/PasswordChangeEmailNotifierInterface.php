<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Notifier\Email;

use DateTimeInterface;

interface PasswordChangeEmailNotifierInterface
{
    public function notify(string $affectedUserId, DateTimeInterface $changedAt): void;
}
