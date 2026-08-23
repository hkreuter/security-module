<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Model;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAUserServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordHistoryServiceInterface;

/**
 * User model extended
 *
 * @mixin \OxidEsales\Eshop\Application\Model\User
 * @eshopExtension
 */
class User extends User_parent
{
    /** @phpstan-ignore missingType.return (inherited from parent without return type) */
    protected function onLogin($userName, #[\SensitiveParameter] $password)
    {
        parent::onLogin($userName, $password);

        $userId = $this->getId();
        if ($userId && !$this->isAdmin()) {
            $twoFAUserService = $this->getService(TwoFAUserServiceInterface::class);
            if ($twoFAUserService->isTwoFARequired($userId) && !$twoFAUserService->isChallengeVerified($userId)) {
                $twoFAUserService->startChallengeForUser($userId);
            }
        }
    }

    /** @param string $sOXIDQuoted */
    protected function deleteAdditionally($sOXIDQuoted): void
    {
        parent::deleteAdditionally($sOXIDQuoted);

        $userId = trim((string)$sOXIDQuoted, "'");
        if ($userId !== '') {
            $this->getService(PasswordHistoryServiceInterface::class)->purgeForUser($userId);
        }
    }
}
