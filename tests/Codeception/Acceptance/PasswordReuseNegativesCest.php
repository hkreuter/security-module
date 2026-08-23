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
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

#[Group('oe_security_module')]
final class PasswordReuseNegativesCest extends BaseCest
{
    private const HISTORY_TABLE = 'oesm_password_history';
    private const RECENTLY_USED = 'OESECURITYMODULE_PASSWORD_RECENTLY_USED';
    private const WELCOME = 'MESSAGE_WELCOME_REGISTERED_USER';
    private const NEW_USER_LOGIN = 'new_test_user@oxid-esales.dev';

    private string $originalPasswordHash = '';
    private string $originalPasswordSalt = '';

    public function _before(AcceptanceTester $I): void
    {
        $this->setCaptchaState(false);
        $this->setHoneyPotCaptchaState(false);
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
        $userId = $this->getExistingUserData()['userId'];

        $I->updateInDatabase(
            'oxuser',
            [
                'oxpassword' => $this->originalPasswordHash,
                'oxpasssalt' => $this->originalPasswordSalt,
            ],
            ['oxid' => $userId]
        );

        $newUserId = (string) $I->grabFromDatabase('oxuser', 'oxid', ['oxusername' => self::NEW_USER_LOGIN]);
        if ($newUserId !== '') {
            $this->clearPasswordHistory($newUserId);
            $I->deleteFromDatabase('oxuser', ['oxusername' => self::NEW_USER_LOGIN]);
        }

        $this->clearPasswordHistory($userId);
        $this->setReusePreventionState(false);
        $this->setNotificationState(false);

        $I->deleteAllEmails();
    }

    public function registrationIsNeverBlockedAndNeverNotifies(AcceptanceTester $I): void
    {
        $I->wantToTest('storefront registration is initial establishment: no reuse block and no notification (BR004)');

        $newUser = $this->getNewUserData();

        $registrationPage = $I->openShop()->openUserRegistrationPage();
        $registrationPage->enterUserLoginData($newUser['loginData']);
        $registrationPage->enterAddressData($newUser['address']);
        $registrationPage->registerUser();

        $I->dontSee(Translator::translate(self::RECENTLY_USED));
        $I->see(Translator::translate(self::WELCOME));

        $newUserId = (string) $I->grabFromDatabase('oxuser', 'oxid', ['oxusername' => self::NEW_USER_LOGIN]);
        $I->assertNotEmpty($newUserId);
        $I->dontSeeInDatabase(self::HISTORY_TABLE, ['oxuserid' => $newUserId]);
    }

    public function profileSaveWithoutPasswordChangeRecordsNothingAndNotifiesNobody(AcceptanceTester $I): void
    {
        $I->wantToTest('a My-Account address save with the password untouched adds no history row (BR019)');

        $userData = $this->getExistingUserData();

        $homePage = $I->openShop();
        $homePage->loginUser($userData['userLoginName'], $userData['userPassword']);

        $addressPage = $homePage
            ->openAccountPage()
            ->openUserAddressPage()
            ->openUserBillingAddressForm();

        $hashBeforeSave = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userData['userId']]);

        $addInfoMarker = 'BR019 profile save without a password change';
        $I->fillField($addressPage->billAdditionalInfo, $addInfoMarker);
        $addressPage->saveAddress();

        $I->seeInDatabase('oxuser', ['oxid' => $userData['userId'], 'oxaddinfo' => $addInfoMarker]);

        $hashAfterSave = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userData['userId']]);
        $I->assertSame($hashBeforeSave, $hashAfterSave);
        $I->dontSeeInDatabase(self::HISTORY_TABLE, ['oxuserid' => $userData['userId']]);
    }

    public function deletingAnAccountPurgesItsPasswordHistory(AcceptanceTester $I): void
    {
        $I->wantToTest('deleting an account leaves it with zero stored previous-password rows (BR016)');

        $repository = ContainerFacade::get(PasswordHistoryRepositoryInterface::class);

        $deletedUserId = substr(uniqid('pwhneg', true), 0, 32);
        $survivingUserId = $this->getExistingUserData()['userId'];

        $user = oxNew(User::class);
        $user->setId($deletedUserId);
        $user->assign([
            'oxusername' => uniqid('neg_', true) . '@oxid-esales.dev',
            'oxactive' => 1,
        ]);
        $user->save();

        $repository->append($deletedUserId, 'old-negatives-hash', new DateTimeImmutable('2026-01-01 09:00:00'));
        $repository->append($survivingUserId, 'keep-negatives-hash', new DateTimeImmutable('2026-01-02 09:00:00'));
        $I->assertSame(1, $repository->countForUser($deletedUserId));

        $toDelete = oxNew(User::class);
        $toDelete->load($deletedUserId);
        $I->assertTrue($toDelete->delete());

        $I->assertSame(0, $repository->countForUser($deletedUserId));
        $I->assertSame(1, $repository->countForUser($survivingUserId));
    }
}
