<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

interface ExpirationServiceInterface
{
    public function calculate(): \DateTimeImmutable;
}
