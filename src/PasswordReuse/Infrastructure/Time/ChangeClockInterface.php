<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Time;

use DateTimeImmutable;

interface ChangeClockInterface
{
    public function now(): DateTimeImmutable;
}
