<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;

/**
 * @mixin \OxidEsales\Eshop\Application\Controller\AccountPasswordController
 * @eshopExtension
 */
class AccountPasswordController extends AccountPasswordController_parent
{
    public function changePassword()
    {
        $user = $this->getUser();
        if (!$user) {
            return parent::changePassword();
        }

        $request = Registry::getRequest();
        $candidate = (string)$request->getRequestParameter('password_new');
        $confirmation = (string)$request->getRequestParameter('password_new_confirm');
        $oldPassword = (string)$request->getRequestParameter('password_old');

        // Reuse is evaluated only for an otherwise-valid, authenticated change: the
        // confirmation must match and the current password must be proven first, so
        // core validation errors win and no reuse hint leaks before authentication.
        if ($candidate === '' || $candidate !== $confirmation || !$user->isSamePassword($oldPassword)) {
            return parent::changePassword();
        }

        $userId = (string)$user->getId();
        $currentHash = (string)$this->getService(StoredPasswordReaderInterface::class)
            ->getStoredPasswordHash($userId);

        try {
            $this->getService(PasswordReuseGuardServiceInterface::class)
                ->guardChange($userId, $candidate, $currentHash);
        } catch (PasswordReuseException $exception) {
            return Registry::getUtilsView()->addErrorToDisplay(PasswordReuseException::MESSAGE_KEY, false, true);
        } catch (PasswordReuseCheckException $exception) {
            return Registry::getUtilsView()->addErrorToDisplay(PasswordReuseCheckException::MESSAGE_KEY, false, true);
        }

        $this->getService(ConfirmedChangeRegistryInterface::class)->confirm($userId);

        return parent::changePassword();
    }
}
