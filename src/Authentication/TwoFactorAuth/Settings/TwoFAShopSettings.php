<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;

class TwoFAShopSettings implements TwoFAShopSettingsInterface
{
    public const ACTIVE = 'oeSecurityTwoFactorAuthEnabled';

    public const TWO_FACTOR_TYPE = 'oeSecurityTwoFactorAuthType';

    public const API_CHALLENGE_LIFETIME = 'oeSecurityTwoFactorAuthApiChallengeLifetime';

    public const OTP_CODE_LIFETIME = 'oeSecurityTwoFactorAuthOtpCodeLifetime';

    public function __construct(
        private Config $config,
        private ModuleSettingServiceInterface $moduleSettingService,
    ) {
    }

    public function isTwoFactorAuthEnabled(): bool
    {
        return $this->moduleSettingService->getBoolean(self::ACTIVE, Module::MODULE_ID);
    }

    public function getTwoFactorAuthType(): string
    {
        return $this->moduleSettingService->getString(self::TWO_FACTOR_TYPE, Module::MODULE_ID)
            ->trim()
            ->toString();
    }

    public function getApiChallengeLifetime(): int
    {
        return $this->moduleSettingService->getInteger(self::API_CHALLENGE_LIFETIME, Module::MODULE_ID);
    }

    public function getOtpCodeLifetime(): int
    {
        return $this->moduleSettingService->getInteger(self::OTP_CODE_LIFETIME, Module::MODULE_ID);
    }

    public function getVerificationUrl(): string
    {
        return $this->config->getShopHomeUrl() . 'cl=oesm_twofactorauth';
    }

    public function getAccountUrl(): string
    {
        return $this->config->getShopHomeUrl() . 'cl=account';
    }
}
