<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\SecurityModule\Shared\Model\User2FAInterface;

interface UserServiceInterface
{
    public function requiresTwoFactorAuth(User2FAInterface $user): bool;
}
