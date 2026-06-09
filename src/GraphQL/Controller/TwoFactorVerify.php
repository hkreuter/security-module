<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Controller;

use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\GraphQL\Base\Service\Token;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

/**
 * Second step of the oxapi 2FA exchange: a client presents the short-lived challenge token
 * (mfa_pending) plus the OTP code, and receives a full token in return. Not #[Logged] — the
 * challenge token is anonymous, so the mutation must be reachable without a logged-in session.
 */
final class TwoFactorVerify
{
    private const CLAIM_MFA_PENDING = 'mfa_pending';

    public function __construct(
        private readonly Token $tokenService,
        private readonly Legacy $legacy,
        private readonly TwoFAServiceInterface $twoFAService,
        private readonly RefreshTokenServiceInterface $refreshTokenService,
    ) {
    }

    #[Mutation]
    public function verifyTwoFactorToken(string $otp): string
    {
        $user = $this->resolveVerifiedUser($otp);

        $accessToken = $this->tokenService->createTokenForUser($user)->toString();

        // Consume only after the token is minted, so a failed mint stays retryable.
        $this->twoFAService->consumeChallenge((string)$user->id()->val());

        return $accessToken;
    }

    /**
     * Validate the challenge token + OTP, then return the now-authenticated user.
     */
    private function resolveVerifiedUser(string $otp): User
    {
        if ($this->tokenService->getTokenClaim(self::CLAIM_MFA_PENDING, false) !== true) {
            throw new InvalidToken('Not a two-factor challenge token');
        }

        $userId = (string)$this->tokenService->getTokenClaim(Token::CLAIM_USERID);
        $this->twoFAService->verify($userId, $otp);

        return new User($this->legacy->getUserModel($userId), false);
    }
}
