<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Service;

interface ChallengeTokenValidatorServiceInterface
{
    /**
     * Validate the current request's 2FA challenge token (mfa_pending + mfa_exp claims) and
     * return the user id it was issued for. Throws when the token is not a challenge token or
     * the challenge window has expired.
     */
    public function validateAndGetUserId(): string;
}
