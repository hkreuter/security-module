<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Storefront\Customer\Exception;

use OxidEsales\GraphQL\Base\Exception\Error;
use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use Throwable;

/**
 * Client-safe GraphQL error for an OXAPI password change/reset rejected by the policy or reuse guard.
 */
final class PasswordChangeRejected extends Error
{
    private const REUSED = 'The new password was used recently and cannot be reused';
    private const POLICY = 'The new password does not meet the password policy';
    private const CHECK_FAILED = 'The new password could not be verified, please try again';

    public static function reused(?Throwable $previous = null): self
    {
        return new self(self::REUSED, $previous);
    }

    public static function policyViolation(?Throwable $previous = null): self
    {
        return new self(self::POLICY, $previous);
    }

    public static function checkFailed(?Throwable $previous = null): self
    {
        return new self(self::CHECK_FAILED, $previous);
    }

    private function __construct(string $message, ?Throwable $previous)
    {
        parent::__construct(
            message: $message,
            previous: $previous,
            category: ErrorCategories::REQUESTERROR,
        );
    }
}
