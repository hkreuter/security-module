<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception;

class TwoFactorAuthRequiredException extends \RuntimeException
{
    public function __construct(
        private readonly string $userId,
        private readonly string $context = 'frontend',
    ) {
        parent::__construct('Two-factor authentication required');
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getContext(): string
    {
        return $this->context;
    }
}
