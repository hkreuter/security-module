<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\Controller;

use LogicException;
use OxidEsales\GraphQL\Base\DataType\Login;
use OxidEsales\GraphQL\Base\DataType\LoginInterface;
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
 *
 * graphql-base is an OPTIONAL (require-dev) module, so its services are injected optionally
 * (`@?` in services.yaml) — that lets the shop-wide container compile when graphql-base is absent.
 * The accessors below fail loud (LogicException) rather than suppressing the null case; it cannot
 * actually occur, because without graphql-base the schema is never built and these mutations are
 * never reached.
 */
final class TwoFactorVerify
{
    private const CLAIM_MFA_PENDING = 'mfa_pending';
    private const BASE_REQUIRED = 'graphql-base is required for the oxapi two-factor verify mutations';

    public function __construct(
        private readonly TwoFAServiceInterface $twoFAService,
        private readonly ?Token $tokenService = null,
        private readonly ?Legacy $legacy = null,
        private readonly ?RefreshTokenServiceInterface $refreshTokenService = null,
    ) {
    }

    #[Mutation]
    public function verifyTwoFactorToken(string $otp): string
    {
        $user = $this->resolveVerifiedUser($otp);

        $accessToken = $this->tokenService()->createTokenForUser($user)->toString();

        // Consume only after the token is minted, so a failed mint stays retryable.
        $this->twoFAService->consumeChallenge((string)$user->id()->val());

        return $accessToken;
    }

    #[Mutation]
    public function verifyTwoFactorLogin(string $otp): LoginInterface
    {
        $user = $this->resolveVerifiedUser($otp);

        $login = new Login(
            refreshToken: $this->refreshTokenService()->createRefreshTokenForUser($user),
            accessToken: $this->tokenService()->createTokenForUser($user),
        );

        // Consume only after both tokens are minted, so a failure stays retryable.
        $this->twoFAService->consumeChallenge((string)$user->id()->val());

        return $login;
    }

    /**
     * Validate the challenge token + OTP, then return the now-authenticated user.
     */
    private function resolveVerifiedUser(string $otp): User
    {
        if ($this->tokenService()->getTokenClaim(self::CLAIM_MFA_PENDING, false) !== true) {
            throw new InvalidToken('Not a two-factor challenge token');
        }

        $userId = (string)$this->tokenService()->getTokenClaim(Token::CLAIM_USERID);
        $this->twoFAService->verify($userId, $otp);

        return new User($this->legacy()->getUserModel($userId), false);
    }

    private function tokenService(): Token
    {
        return $this->tokenService ?? throw new LogicException(self::BASE_REQUIRED);
    }

    private function legacy(): Legacy
    {
        return $this->legacy ?? throw new LogicException(self::BASE_REQUIRED);
    }

    private function refreshTokenService(): RefreshTokenServiceInterface
    {
        return $this->refreshTokenService ?? throw new LogicException(self::BASE_REQUIRED);
    }
}
