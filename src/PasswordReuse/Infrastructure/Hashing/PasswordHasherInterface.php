<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing;

interface PasswordHasherInterface
{
    public function hash(#[\SensitiveParameter] string $plaintext): string;

    public function verifyPassword(#[\SensitiveParameter] string $plaintext, string $hash): bool;
}
