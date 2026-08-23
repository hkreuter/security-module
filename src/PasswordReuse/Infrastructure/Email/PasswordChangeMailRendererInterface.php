<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email;

interface PasswordChangeMailRendererInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data): string;
}
