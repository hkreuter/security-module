<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

class OTPGeneratorService implements OTPGeneratorServiceInterface
{
    public function __construct(
        private readonly ModuleSettingsServiceInterface $moduleSettingsService,
    ) {
    }

    public function generate(): string
    {
        $length = $this->moduleSettingsService->getOtpLength();
        $max = (int) str_repeat('9', $length);
        $code = random_int(0, $max);

        return str_pad((string) $code, $length, '0', STR_PAD_LEFT);
    }
}
