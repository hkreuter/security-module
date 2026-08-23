<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use Codeception\Attribute\Group;
use DateTimeImmutable;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing\PasswordHasherInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

#[Group('flow:storefront-change')]
#[Group('oe_security_module')]
final class PasswordReuseStorefrontChangeCest extends BaseCest
{
    private const RECENTLY_USED = 'OESECURITYMODULE_PASSWORD_RECENTLY_USED';
    private const PASSWORD_CHANGED = 'MESSAGE_PASSWORD_CHANGED';
    private const PREVIOUS_PASSWORD = 'Pr3vious-passw0rd!';
    private const NEW_PASSWORD = 'Fr3sh-passw0rd!';

    private string $originalPasswordHash = '';
    private string $originalPasswordSalt = '';

    public function _before(AcceptanceTester $I): void
    {
        $this->setCaptchaState(false);
        $this->setPasswordState(false);
        $this->setReusePreventionState(true);
        $this->setNotificationState(true);

        $userId = $this->getExistingUserData()['userId'];
        $this->originalPasswordHash = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userId]);
        $this->originalPasswordSalt = (string) $I->grabFromDatabase('oxuser', 'oxpasssalt', ['oxid' => $userId]);

        $I->deleteAllEmails();
    }

    public function _after(AcceptanceTester $I): void
    {
        $userData = $this->getExistingUserData();

        $I->updateInDatabase(
            'oxuser',
            [
                'oxpassword' => $this->originalPasswordHash,
                'oxpasssalt' => $this->originalPasswordSalt,
            ],
            ['oxid' => $userData['userId']]
        );

        $this->clearPasswordHistory($userData['userId']);
        $this->setReusePreventionState(false);
        $this->setNotificationState(false);

        $I->deleteAllEmails();
    }

    public function changeToCurrentPasswordIsGracefullyRejected(AcceptanceTester $I): void
    {
        $I->wantToTest('reusing the current password is rejected inline and never fatals the page');

        $userData = $this->getExistingUserData();
        $currentPassword = $userData['userPassword'];

        $this->submitPasswordChange($I, $userData, $currentPassword, $currentPassword);

        $I->see(Translator::translate(self::RECENTLY_USED));
        $I->dontSee(Translator::translate(self::PASSWORD_CHANGED));
    }

    public function changeToPreviousPasswordIsGracefullyRejected(AcceptanceTester $I): void
    {
        $I->wantToTest('reusing a remembered previous password is rejected inline');

        $userData = $this->getExistingUserData();
        $currentPassword = $userData['userPassword'];
        $this->seedPreviousPassword($userData['userId'], self::PREVIOUS_PASSWORD);

        $this->submitPasswordChange($I, $userData, $currentPassword, self::PREVIOUS_PASSWORD);

        $I->see(Translator::translate(self::RECENTLY_USED));
        $I->dontSee(Translator::translate(self::PASSWORD_CHANGED));
    }

    public function changeToFreshPasswordSucceedsAndNotifies(AcceptanceTester $I): void
    {
        $I->wantToTest('a fresh password is accepted and triggers the change-notification email');

        $userData = $this->getExistingUserData();
        $currentPassword = $userData['userPassword'];

        $this->submitPasswordChange($I, $userData, $currentPassword, self::NEW_PASSWORD);

        $I->see(Translator::translate(self::PASSWORD_CHANGED));
        $I->dontSee(Translator::translate(self::RECENTLY_USED));

        $I->openRecentEmail();
        $I->seeInEmailTo($userData['userLoginName']);
    }

    private function submitPasswordChange(
        AcceptanceTester $I,
        array $userData,
        string $currentPassword,
        string $candidatePassword,
    ): void {
        $homePage = $I->openShop();
        $homePage->loginUser($userData['userLoginName'], $userData['userPassword']);

        $homePage
            ->openAccountPage()
            ->seePageOpened()
            ->seeUserAccount($userData)
            ->openChangePasswordPage()
            ->changePassword($currentPassword, $candidatePassword, $candidatePassword);
    }

    private function seedPreviousPassword(string $userId, string $plaintext): void
    {
        $hash = ContainerFacade::get(PasswordHasherInterface::class)->hash($plaintext);
        ContainerFacade::get(PasswordHistoryRepositoryInterface::class)
            ->append($userId, $hash, new DateTimeImmutable());
    }
}
