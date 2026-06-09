<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Service;

use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\SecurityModule\GraphQL\DataType\TwoFAPendingUser;

/**
 * Decorates graphql-base's RefreshTokenService so a user mid-2FA-challenge never receives a
 * usable refresh token.
 *
 * The login() mutation issues both an access token and a refresh token. Without this guard a
 * TwoFAPendingUser would get a fully-valid refresh token and could exchange it for a complete
 * access token via refreshToken(), bypassing 2FA entirely. Here createRefreshTokenForUser()
 * returns an empty string for a pending user (no refresh token is persisted), so the only way
 * forward is the verifyTwoFactor* mutation. Everything else is delegated to the inner service.
 *
 * Registered with `decoration_on_invalid: ignore` + `autowire: false` → silently dropped and
 * never autoloaded when the optional graphql-base module is absent.
 */
final class PendingAwareRefreshTokenService implements RefreshTokenServiceInterface
{
    public function __construct(private readonly RefreshTokenServiceInterface $inner)
    {
    }

    public function createRefreshTokenForUser(UserInterface $user): string
    {
        if ($user instanceof TwoFAPendingUser) {
            return '';
        }

        return $this->inner->createRefreshTokenForUser($user);
    }

    public function refreshToken(string $refreshToken, string $fingerprintHash): string
    {
        return $this->inner->refreshToken($refreshToken, $fingerprintHash);
    }
}
