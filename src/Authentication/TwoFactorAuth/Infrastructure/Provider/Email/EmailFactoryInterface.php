<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email;

use OxidEsales\Eshop\Core\Email;

interface EmailFactoryInterface
{
    public function create(): Email;
}
