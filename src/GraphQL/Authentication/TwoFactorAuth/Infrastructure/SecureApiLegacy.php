<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorRequiredException;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\DataType\TwoFAPendingUser;

/**
 * Decorates graphql-base's Legacy so the oxapi login flow can enforce 2FA.
 *
 * Plain Legacy::login() wraps every exception (including TwoFactorRequiredException) into a
 * generic InvalidLogin, which makes 2FA authentication impossible through the API. This decorator
 * reimplements login() to catch TwoFactorRequiredException specifically and return a
 * TwoFAPendingUser (→ short-lived, anonymous challenge token), while keeping the normal
 * bad-credentials behaviour. All other Legacy methods are delegated to the inner instance.
 *
 * Registered with `decoration_on_invalid: ignore` + `autowire: false`, so when the optional
 * graphql-base module is absent this decorator is silently dropped and never autoloaded.
 */
final class SecureApiLegacy extends Legacy
{
    public function __construct(private readonly Legacy $inner)
    {
        // Delegating decorator: the inner Legacy is fully constructed; we never use parent state.
    }

    public function login(?string $username = null, ?string $password = null): UserInterface
    {
        $user = $this->inner->getUserModel();

        if ($username && $password) {
            try {
                $user->login($username, $password);
            } catch (TwoFactorRequiredException) {
                return new TwoFAPendingUser($user);
            } catch (Exception $exception) {
                throw new InvalidLogin('Username/password combination is invalid', previous: $exception);
            }

            return new User($user, false);
        }

        $user->setId($this->inner->createUniqueIdentifier());

        return new User($user, true);
    }

    public function getUserModel(?string $userId = null): EshopUserModel
    {
        return $this->inner->getUserModel($userId);
    }

    public function getConfigParam(string $param): mixed
    {
        return $this->inner->getConfigParam($param);
    }

    public function getShopUrl(): string
    {
        return $this->inner->getShopUrl();
    }

    public function getShopId(): int
    {
        return $this->inner->getShopId();
    }

    public function getLanguageId(): int
    {
        return $this->inner->getLanguageId();
    }

    public function isValidEmail(string $email): bool
    {
        return $this->inner->isValidEmail($email);
    }

    public function getEmail(): Email
    {
        return $this->inner->getEmail();
    }

    /**
     * @return string[]
     */
    public function getUserGroupIds(?string $userId): array
    {
        return $this->inner->getUserGroupIds($userId);
    }

    public function createUniqueIdentifier(): string
    {
        return $this->inner->createUniqueIdentifier();
    }
}
