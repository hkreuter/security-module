<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Service;

use DateTimeImmutable;
use LogicException;
use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Service\Token;

/**
 * Validates the oxapi 2FA challenge token that the verify controller receives.
 *
 * The JWT `exp` (graphql-base default 8h) is far too long for a challenge token, so we stamp and
 * enforce our own short, resend-independent window (mfa_exp) on top of the mfa_pending marker.
 *
 * graphql-base is an OPTIONAL (require-dev) module, so its Token service is injected optionally
 * (`@?` in services.yaml). The accessor fails loud rather than suppressing the null case; it
 * cannot actually occur, because without graphql-base the schema is never built and the verify
 * mutations that use this service are never reached.
 */
final class ChallengeTokenValidatorService implements ChallengeTokenValidatorServiceInterface
{
    private const CLAIM_MFA_PENDING = 'mfa_pending';
    private const CLAIM_MFA_EXP = 'mfa_exp';
    private const BASE_REQUIRED = 'graphql-base is required for the oxapi two-factor challenge validation';

    public function __construct(private readonly ?Token $tokenService = null)
    {
    }

    public function validateAndGetUserId(): string
    {
        $tokenService = $this->tokenService();

        if ($tokenService->getTokenClaim(self::CLAIM_MFA_PENDING, false) !== true) {
            throw new InvalidToken('Not a two-factor challenge token');
        }

        $challengeExpiresAt = (int)$tokenService->getTokenClaim(self::CLAIM_MFA_EXP, 0);
        if ($challengeExpiresAt < (new DateTimeImmutable())->getTimestamp()) {
            throw new InvalidToken('Two-factor challenge has expired');
        }

        return (string)$tokenService->getTokenClaim(Token::CLAIM_USERID);
    }

    private function tokenService(): Token
    {
        return $this->tokenService ?? throw new LogicException(self::BASE_REQUIRED);
    }
}
