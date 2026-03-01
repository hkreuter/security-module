<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

interface ExpirationServiceInterface
{
    public function calculate(): \DateTimeImmutable;
}
