<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

class ExpirationService implements ExpirationServiceInterface
{
    public function __construct(
        private readonly ModuleSettingsServiceInterface $moduleSettingsService,
    ) {
    }

    public function calculate(): \DateTimeImmutable
    {
        $ttl = $this->moduleSettingsService->getOtpLifetime();

        return new \DateTimeImmutable("+{$ttl} seconds");
    }
}
