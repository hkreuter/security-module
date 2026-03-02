<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Component;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorAuthRequiredException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFactorAuthOrchestratorInterface;

/**
 * @mixin \OxidEsales\Eshop\Application\Component\UserComponent
 * @eshopExtension
 */
class UserComponent extends UserComponent_parent
{
    public function login(): ?string
    {
        try {
            return parent::login();
        } catch (TwoFactorAuthRequiredException $exception) {
            $this->initiateTwoFactorAuth($exception);
            return null;
        }
    }

    private function initiateTwoFactorAuth(TwoFactorAuthRequiredException $exception): void
    {
        $userId = $exception->getUserId();

        $orchestrator = $this->getService(TwoFactorAuthOrchestratorInterface::class);
        $orchestrator->initiate(
            $userId,
            $this->loadUserEmail($userId),
            Registry::getSession()->getId(),
            'frontend'
        );

        $this->getParent()->addTplParam('oeSmTwoFactorAuthRequired', true);
        $this->getParent()->addTplParam('oeSmTwoFactorAuthUserId', $userId);
    }

    protected function loadUserEmail(string $userId): string
    {
        $user = oxNew(User::class);
        $user->load($userId);

        return (string) $user->getFieldData('oxusername');
    }
}
