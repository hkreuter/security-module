<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Model\BaseModel;

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

        if (!$this->settings->isTwoFactorAuthEnabled()) {
            return false;
        }

        return $this->isUserTwoFactorEnabled($userId);
    }

    public function isUserTwoFactorEnabled(string $userId): bool
    {
        $user = oxNew(User::class);
        if (!$user->load($userId)) {
            return false;
        }

        return (bool) $user->getFieldData('oesm2faenabled');
    }

    public function setUserTwoFactorEnabled(string $userId, bool $enabled): void
    {
        $user = oxNew(User::class);
        if (!$user->load($userId)) {
            return;
        }

        $user->assign(['oesm2faenabled' => $enabled ? 1 : 0]);
        $user->save();
    }

    public function initiateTwoFactor(string $userId, string $sid, string $context): void
    {
        $this->orchestrator->initiate($userId, $sid, $context);
    }
}
