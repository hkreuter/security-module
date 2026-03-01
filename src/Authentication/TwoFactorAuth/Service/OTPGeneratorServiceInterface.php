<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

interface OTPGeneratorServiceInterface
{
    public function generate(): string;
}
