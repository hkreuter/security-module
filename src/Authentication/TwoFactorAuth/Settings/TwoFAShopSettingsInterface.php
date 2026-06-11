<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings;

interface TwoFAShopSettingsInterface
{
    public function isTwoFactorAuthEnabled(): bool;

    public function getTwoFactorAuthType(): string;

    public function getApiChallengeLifetime(): int;

    public function getVerificationUrl(): string;

    public function getAccountUrl(): string;
}
