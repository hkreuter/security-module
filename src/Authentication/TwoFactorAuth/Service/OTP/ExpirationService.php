<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;

class ExpirationService implements ExpirationServiceInterface
{
    public function __construct(
        private readonly ModuleSettingsServiceInterface $settings,
    ) {
    }

    public function calculate(): \DateTimeImmutable
    {
        $ttl = $this->settings->getOtpTtl();

        return new \DateTimeImmutable(sprintf('+%d seconds', $ttl));
    }
}
