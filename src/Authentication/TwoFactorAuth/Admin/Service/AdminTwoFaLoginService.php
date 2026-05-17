<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Admin\Service;

use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Utils;
use OxidEsales\EshopCommunity\Internal\Framework\Session\SessionInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;

readonly class AdminTwoFaLoginService implements AdminTwoFaLoginServiceInterface
{
    public const ADMIN_SESSION_KEY = 'pending_admin_authorized_user';

    public function __construct(
        private TwoFAServiceInterface $twoFAService,
        private SessionInterface $session,
        private Session $oxSession,
        private Utils $utils,
    ) {
    }

    public function startChallenge(string $userId): void
    {
        $this->session->set(self::ADMIN_SESSION_KEY, $userId);
        $this->twoFAService->triggerChallenge($userId);
    }

    public function completeLogin(string $userId): void
    {
        $this->session->remove(self::ADMIN_SESSION_KEY);

        try {
            $this->session->set('auth', $userId);
        } finally {
            $this->twoFAService->invalidateChallenge($userId);
        }

        $this->oxSession->regenerateSessionId();
        $this->utils->redirect('index.php?cl=admin_start&' . $this->oxSession->sid(), false, 302);
    }

    public function abandonLogin(?string $userId): void
    {
        $this->session->remove(self::ADMIN_SESSION_KEY);

        if ($userId !== null) {
            $this->twoFAService->invalidateChallenge($userId);
        }

        $this->utils->redirect('index.php?cl=login', true, 302);
    }
}
