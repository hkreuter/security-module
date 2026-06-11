<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Authentication\Exception;

use OxidEsales\GraphQL\Base\Exception\Error;
use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use Throwable;

/**
 * Client-aware GraphQL error for a failed oxapi 2FA challenge verification. Translates the security
 * domain's CodeValidationException family (wrong / expired / too-many-attempts / already-consumed)
 * into a single generic, client-safe message at the oxapi boundary, so graphqlite surfaces it
 * instead of masking it as "Internal server error". Deliberately generic — it does not reveal which
 * specific check failed (no oracle for "does a challenge exist" or "attempts remaining").
 *
 * Lives in the GraphQL layer (extends graphql-base's Error), so it is only autoloaded when the
 * verify controller runs — i.e. only when the optional graphql-base module is present.
 */
final class TwoFactorChallengeException extends Error
{
    private const MESSAGE = 'Invalid or expired two-factor code';

    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            message: self::MESSAGE,
            previous: $previous,
            category: ErrorCategories::REQUESTERROR,
        );
    }
}
