<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Model;

interface User2FAInterface
{
    public function is2FAEnabled(): bool;

    public function set2FAEnabled(bool $enabled): void;
}
