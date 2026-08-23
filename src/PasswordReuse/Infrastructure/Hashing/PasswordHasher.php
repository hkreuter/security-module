<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;

class PasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordServiceBridgeInterface $passwordService,
    ) {
    }

    public function hash(#[\SensitiveParameter] string $plaintext): string
    {
        return $this->passwordService->hash($plaintext);
    }

    public function verifyPassword(#[\SensitiveParameter] string $plaintext, string $hash): bool
    {
        return $this->passwordService->verifyPassword($plaintext, $hash);
    }
}
