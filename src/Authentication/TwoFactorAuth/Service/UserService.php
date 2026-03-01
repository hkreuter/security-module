<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly TwoFactorAuthOrchestratorInterface $orchestrator,
        private readonly ModuleSettingsServiceInterface $settings,
        private readonly bool $mandatoryForAdmin,
    ) {
    }

    public function isTwoFactorRequired(string $userId, bool $isAdmin): bool
    {
        if ($isAdmin && $this->mandatoryForAdmin) {
            return true;
        }

        return $this->settings->isTwoFactorAuthEnabled();
    }

    public function initiateTwoFactor(string $userId, string $sid, string $context): void
    {
        $this->orchestrator->initiate($userId, $sid, $context);
    }
}
