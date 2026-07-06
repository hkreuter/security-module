<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Controller;

use LogicException;
use OxidEsales\GraphQL\Base\DataType\Login;
use OxidEsales\GraphQL\Base\DataType\LoginInterface;
use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\GraphQL\Base\Service\Token;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\CodeValidationException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Exception\TwoFactorChallengeException;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Service\ChallengeTokenValidatorServiceInterface;
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
final class TwoFactorAuthController
{
    private const BASE_REQUIRED = 'graphql-base is required for the oxapi two-factor verify mutations';

    public function __construct(
        private readonly TwoFAServiceInterface $twoFAService,
        private readonly ChallengeTokenValidatorServiceInterface $challengeValidator,
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
        $this->twoFAService->consumeChallenge((string)$user->id());

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
        $this->twoFAService->consumeChallenge((string)$user->id());

        return $login;
    }

    /**
     * Validate the challenge token + OTP, then return the now-authenticated user.
     */
    private function resolveVerifiedUser(string $otp): User
    {
        $userId = $this->challengeValidator->validateAndGetUserId();

        // Translate the security domain's validation failures (wrong/expired/too-many/consumed) into
        // a client-aware GraphQL error; otherwise graphqlite masks them as "Internal server error".
        try {
            $this->twoFAService->verify($userId, $otp);
        } catch (CodeValidationException $exception) {
            throw new TwoFactorChallengeException(previous: $exception);
        }

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
