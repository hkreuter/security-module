<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Shared\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;

/**
 * @mixin \OxidEsales\Eshop\Application\Controller\Admin\UserMain
 * @eshopExtension
 */
class UserMain extends UserMain_parent
{
    public function save()
    {
        $editedUserId = (string)$this->getEditObjectId();
        $candidate = (string)Registry::getRequest()->getRequestEscapedParameter('newPassword');

        if ($editedUserId === '' || $editedUserId === '-1' || $candidate === '') {
            return parent::save();
        }

        $currentHash = (string)$this->getService(StoredPasswordReaderInterface::class)
            ->getStoredPasswordHash($editedUserId);
        if ($currentHash === '') {
            return parent::save();
        }

        try {
            $this->getService(PasswordReuseGuardServiceInterface::class)
                ->guardChange($editedUserId, $candidate, $currentHash);
        } catch (PasswordReuseException $exception) {
            return Registry::getUtilsView()->addErrorToDisplay(PasswordReuseException::MESSAGE_KEY, false, true);
        } catch (PasswordReuseCheckException $exception) {
            return Registry::getUtilsView()->addErrorToDisplay(PasswordReuseCheckException::MESSAGE_KEY, false, true);
        }

        $this->getService(ConfirmedChangeRegistryInterface::class)->confirm($editedUserId);

        return parent::save();
    }
}
