<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email;

interface EmailAdapterInterface
{
    public function send(string $email, string $code): void;
}
