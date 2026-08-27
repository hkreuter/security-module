<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Service;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\GraphQL\Storefront\Customer\DataType\Customer as CustomerDataType;
use OxidEsales\GraphQL\Storefront\Customer\Service\PasswordInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Factory\UserModelFactoryInterface;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

/**
 * Decorates graphql-storefront's customer password service to enforce the password policy and reuse
 * guard on change/reset before delegating.
 */
final class GuardedPasswordService implements PasswordInterface
{
    public function __construct(
        private readonly PasswordInterface $inner,
        private readonly ?AuthenticationServiceInterface $authentication,
        private readonly UserModelFactoryInterface $userModelFactory,
        private readonly PasswordChangeGuardInterface $guard,
    ) {
    }

    public function change(string $old, string $new): CustomerDataType
    {
        $user = $this->currentUser();

        if ($user !== null && $user->isSamePassword($old)) {
            $this->guard->guard((string)$user->getId(), $new);
        }

        return $this->inner->change($old, $new);
    }

    public function resetPasswordByUpdateHash(string $updateHash, string $newPassword, string $repeatPassword): bool
    {
        $user = $this->userModelFactory->create();
        if ($user->loadUserByUpdateId($updateHash)) {
            $this->guard->guard((string)$user->getId(), $newPassword);
        }

        return $this->inner->resetPasswordByUpdateHash($updateHash, $newPassword, $repeatPassword);
    }

    public function sendPasswordForgotEmail(string $email): bool
    {
        return $this->inner->sendPasswordForgotEmail($email);
    }

    private function currentUser(): ?User
    {
        $authenticated = $this->authentication?->getUser();
        if ($authenticated === null || !method_exists($authenticated, 'id')) {
            return null;
        }

        $user = $this->userModelFactory->create();

        return $user->load((string)$authenticated->id()) ? $user : null;
    }
}
