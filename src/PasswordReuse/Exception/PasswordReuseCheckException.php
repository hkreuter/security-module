<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Exception;

use RuntimeException;
use Throwable;

class PasswordReuseCheckException extends RuntimeException
{
    public const MESSAGE_KEY = 'OESECURITYMODULE_PASSWORD_REUSE_CHECK_FAILED';

    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE_KEY, 0, $previous);
    }
}
