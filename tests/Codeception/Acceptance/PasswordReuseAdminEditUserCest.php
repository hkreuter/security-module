<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use Codeception\Attribute\Group;
use DateTimeImmutable;
use OxidEsales\Codeception\Admin\DataObject\AdminUser;
use OxidEsales\Codeception\Admin\DataObject\AdminUserAddresses;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing\PasswordHasherInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

#[Group('flow:admin-edit-user')]
#[Group('oe_security_module')]
final class PasswordReuseAdminEditUserCest extends BaseCest
{
    private const HISTORY_TABLE = 'oesm_password_history';
    private const PREVIOUS_PASSWORD = 'Pr3vious-passw0rd!';
    private const NEW_PASSWORD = 'Fr3sh-passw0rd!';
    private const CREATED_USER_LOGIN = 'admin_created_user@oxid-esales.dev';

    private string $originalPasswordHash = '';
    private string $originalPasswordSalt = '';

    public function _before(AcceptanceTester $I): void
    {
        $this->setReusePreventionState(true);
        $this->setNotificationState(true);

        $userId = $this->getExistingUserData()['userId'];
        $this->originalPasswordHash = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userId]);
        $this->originalPasswordSalt = (string) $I->grabFromDatabase('oxuser', 'oxpasssalt', ['oxid' => $userId]);

        $I->deleteAllEmails();
    }

    public function _after(AcceptanceTester $I): void
    {
        $userId = $this->getExistingUserData()['userId'];

        $I->updateInDatabase(
            'oxuser',
            [
                'oxpassword' => $this->originalPasswordHash,
                'oxpasssalt' => $this->originalPasswordSalt,
            ],
            ['oxid' => $userId]
        );

        $this->clearPasswordHistory($userId);
        $I->deleteFromDatabase('oxuser', ['oxusername' => self::CREATED_USER_LOGIN]);

        $this->setReusePreventionState(false);
        $this->setNotificationState(false);

        $I->deleteAllEmails();
    }

    public function reusedPasswordOnExistingUserIsGracefullyRejected(AcceptanceTester $I): void
    {
        $I->wantToTest('admin editing a user to a recently-used password is rejected without a fatal or a save');

        $userData = $this->getExistingUserData();
        $this->seedPreviousPassword($userData['userId'], self::PREVIOUS_PASSWORD);

        $mainUserPage = $I->loginAdmin()
            ->openUsers()
            ->findByUserName($userData['userLoginName']);
        $mainUserPage->updatePassword(self::PREVIOUS_PASSWORD);

        $I->seeElement($mainUserPage->usernameField);
        $I->seeInField($mainUserPage->usernameField, $userData['userLoginName']);

        $currentHash = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userData['userId']]);
        $I->assertSame($this->originalPasswordHash, $currentHash);
    }

    public function freshPasswordSucceedsAndNotifiesEditedAccount(AcceptanceTester $I): void
    {
        $I->wantToTest('admin setting a fresh password saves it and notifies the edited account');

        $userData = $this->getExistingUserData();

        $mainUserPage = $I->loginAdmin()
            ->openUsers()
            ->findByUserName($userData['userLoginName']);
        $mainUserPage->updatePassword(self::NEW_PASSWORD);

        $currentHash = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userData['userId']]);
        $I->assertNotSame($this->originalPasswordHash, $currentHash);
        $I->assertNotEmpty($currentHash);

        $I->openRecentEmail();
        $I->seeInEmailTo($userData['userLoginName']);
    }

    public function creatingANewUserWithAFirstPasswordIsExempt(AcceptanceTester $I): void
    {
        $I->wantToTest('admin creating a new user with a first password neither blocks nor notifies (BR004)');

        $users = $I->loginAdmin()->openUsers();
        $users->createNewUser($this->buildNewAdminUser(), $this->buildNewAdminUserAddress());

        $I->seeInDatabase('oxuser', ['oxusername' => self::CREATED_USER_LOGIN]);
        $createdUserId = (string) $I->grabFromDatabase('oxuser', 'oxid', ['oxusername' => self::CREATED_USER_LOGIN]);
        $I->dontSeeInDatabase(self::HISTORY_TABLE, ['oxuserid' => $createdUserId]);
    }

    public function savingWithABlankPasswordChangesNothing(AcceptanceTester $I): void
    {
        $I->wantToTest('an admin save with the password field left blank adds no history row and notifies nobody');

        $userData = $this->getExistingUserData();

        $mainUserPage = $I->loginAdmin()
            ->openUsers()
            ->findByUserName($userData['userLoginName']);
        $mainUserPage->updateUsername($userData['userLoginName']);

        $currentHash = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userData['userId']]);
        $I->assertSame($this->originalPasswordHash, $currentHash);
        $I->dontSeeInDatabase(self::HISTORY_TABLE, ['oxuserid' => $userData['userId']]);
    }

    private function buildNewAdminUser(): AdminUser
    {
        $adminUser = new AdminUser();
        $adminUser->setActive(true);
        $adminUser->setUsername(self::CREATED_USER_LOGIN);
        $adminUser->setPassword(self::NEW_PASSWORD);
        $adminUser->setUserRights('user');

        return $adminUser;
    }

    private function buildNewAdminUserAddress(): AdminUserAddresses
    {
        $address = new AdminUserAddresses();
        $address->setTitle('MR');
        $address->setFirstName('New');
        $address->setLastName('User');
        $address->setStreet('Street');
        $address->setStreetNumber('55');
        $address->setZip('5555');
        $address->setCity('City');
        $address->setCountryId('Germany');

        return $address;
    }

    private function seedPreviousPassword(string $userId, string $plaintext): void
    {
        $hash = ContainerFacade::get(PasswordHasherInterface::class)->hash($plaintext);
        ContainerFacade::get(PasswordHistoryRepositoryInterface::class)
            ->append($userId, $hash, new DateTimeImmutable());
    }
}
