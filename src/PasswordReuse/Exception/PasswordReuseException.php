<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Exception;

use OxidEsales\Eshop\Core\Exception\UserException;

class PasswordReuseException extends UserException
{
    public const MESSAGE_KEY = 'OESECURITYMODULE_PASSWORD_RECENTLY_USED';

    public function __construct()
    {
        parent::__construct(self::MESSAGE_KEY);
    }
}
