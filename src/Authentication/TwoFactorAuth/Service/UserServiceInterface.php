<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

interface UserServiceInterface
{
    public function isTwoFactorRequired(string $userId, bool $isAdmin): bool;

    public function isUserTwoFactorEnabled(string $userId): bool;

    public function setUserTwoFactorEnabled(string $userId, bool $enabled): void;

    public function initiateTwoFactor(string $userId, string $sid, string $context): void;
}
