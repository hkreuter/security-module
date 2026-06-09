<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Infrastructure;

use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;

/**
 * SPIKE EXPERIMENT — minimal decorator to validate fail-safe DI registration of a service that
 * structurally depends on the OPTIONAL graphql-base module.
 *
 * The real implementation will reimplement login() to catch TwoFactorRequiredException and return
 * a TwoFAPendingUser, and delegate all other Legacy methods to $inner. For now this only proves
 * the container compiles both when graphql-base is present (decorator active) and absent
 * (decorator dropped via decoration_on_invalid: ignore, never autoloaded via autowire: false).
 */
final class SecureApiLegacy extends Legacy
{
    public function __construct(private readonly Legacy $inner)
    {
        // Intentionally does NOT call parent::__construct — this is a delegating decorator.
    }

    public function login(?string $username = null, ?string $password = null): UserInterface
    {
        return $this->inner->login($username, $password);
    }
}
