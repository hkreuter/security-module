<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\DataType;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Sentinel user returned by SecureApiLegacy when an API login triggers a 2FA challenge.
 *
 * Wraps the already-loaded eShop user model. isAnonymous() is always true, so the resulting
 * challenge token is blocked from #[Logged] mutations; BeforeTokenCreationSubscriber detects this
 * exact type to stamp the mfa_pending claim. Only ever instantiated by SecureApiLegacy (active
 * only when graphql-base is present), so its dependency on the graphql-base UserInterface is safe.
 */
final class TwoFAPendingUser implements UserInterface
{
    public function __construct(private readonly EshopUserModel $userModel)
    {
    }

    public function getEshopModel(): EshopUserModel
    {
        return $this->userModel;
    }

    public function email(): string
    {
        return (string)$this->userModel->getRawFieldData('oxusername');
    }

    public function id(): ID
    {
        return new ID((string)$this->userModel->getId());
    }

    public function isAnonymous(): bool
    {
        return true;
    }
}
