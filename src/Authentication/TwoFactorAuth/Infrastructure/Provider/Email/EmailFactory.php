<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email;

use OxidEsales\Eshop\Core\Email;

class EmailFactory implements EmailFactoryInterface
{
    public function create(): Email
    {
        return oxNew(Email::class);
    }
}
