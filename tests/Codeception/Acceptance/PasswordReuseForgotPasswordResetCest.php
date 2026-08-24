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

#[Group('flow:forgot-password-reset')]
#[Group('oe_security_module')]
final class PasswordReuseForgotPasswordResetCest extends BaseCest
{
    private const RECENTLY_USED = 'OESECURITYMODULE_PASSWORD_RECENTLY_USED';
    private const PASSWORD_CHANGED = 'PASSWORD_CHANGED';
    private const LINK_EXPIRED = 'ERROR_MESSAGE_PASSWORD_LINK_EXPIRED';
    private const PREVIOUS_PASSWORD = 'Pr3vious-passw0rd!';
    private const NEW_PASSWORD = 'Fr3sh-passw0rd!';

    private const EMAIL_FIELD = '#forgotPasswordUserLoginName';
    private const NEW_PASSWORD_FIELD = '#password_new';
    private const CONFIRM_PASSWORD_FIELD = '#password_new_confirm';
    private const SUBMIT_BUTTON = '.submitButton';

    private string $originalPasswordHash = '';
    private string $originalPasswordSalt = '';

    public function _before(AcceptanceTester $I): void
    {
        Translator::switchTranslationDomain(Translator::TRANSLATION_DOMAIN_SHOP);

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
        $userId = $this->getExistingUserData()['userId'];

        $I->updateInDatabase(
            'oxuser',
            [
                'oxpassword' => $this->originalPasswordHash,
                'oxpasssalt' => $this->originalPasswordSalt,
                'oxupdatekey' => '',
                'oxupdateexp' => 0,
            ],
            ['oxid' => $userId]
        );

        $this->clearPasswordHistory($userId);
        $this->setReusePreventionState(false);
        $this->setNotificationState(false);
        $this->setPasswordState(true);

        $I->deleteAllEmails();
    }

    public function reuseRejectionPreservesTokenForRetry(AcceptanceTester $I): void
    {
        $I->wantToTest('a reuse rejection during reset keeps the reset link valid for a fresh retry (BR014)');

        $userData = $this->getExistingUserData();
        $this->seedPreviousPassword($userData['userId'], self::PREVIOUS_PASSWORD);
        $resetToken = $this->requestResetAndGrabToken($I, $userData['userLoginName']);

        $this->submitResetPassword($I, $resetToken, self::PREVIOUS_PASSWORD);

        $I->see(Translator::translate(self::RECENTLY_USED));
        $I->dontSee(Translator::translate(self::PASSWORD_CHANGED));

        $this->submitResetPassword($I, $resetToken, self::NEW_PASSWORD);

        $I->see(Translator::translate(self::PASSWORD_CHANGED));
        $I->dontSee(Translator::translate(self::LINK_EXPIRED));
        $I->dontSee(Translator::translate(self::RECENTLY_USED));
    }

    public function successfulResetSendsNotification(AcceptanceTester $I): void
    {
        $I->wantToTest('a valid reset succeeds and triggers the change-notification email');

        $userData = $this->getExistingUserData();
        $resetToken = $this->requestResetAndGrabToken($I, $userData['userLoginName']);

        $this->submitResetPassword($I, $resetToken, self::NEW_PASSWORD);

        $I->see(Translator::translate(self::PASSWORD_CHANGED));
        $I->dontSee(Translator::translate(self::RECENTLY_USED));

        $I->openRecentEmail();
        $I->seeInEmailTo($userData['userLoginName']);
    }

    private function requestResetAndGrabToken(AcceptanceTester $I, string $email): string
    {
        $I->amOnPage('/en/forgot-password/');
        $I->fillField(self::EMAIL_FIELD, $email);
        $I->click(self::SUBMIT_BUTTON);

        $I->openRecentEmail();
        $body = html_entity_decode($I->grabHtmlBodyFromEmail());
        $I->deleteAllEmails();

        if (!preg_match('/uid=([a-f0-9]{32})/', $body, $matches)) {
            throw new \RuntimeException('The forgot-password email did not contain a reset token.');
        }

        return $matches[1];
    }

    private function submitResetPassword(AcceptanceTester $I, string $resetToken, string $password): void
    {
        $I->amOnPage('?cl=forgotpwd&uid=' . $resetToken . '&lang=1&shp=1');

        $I->fillField(self::NEW_PASSWORD_FIELD, $password);
        $I->fillField(self::CONFIRM_PASSWORD_FIELD, $password);
        $I->click(self::SUBMIT_BUTTON);
    }

    private function seedPreviousPassword(string $userId, string $plaintext): void
    {
        $hash = ContainerFacade::get(PasswordHasherInterface::class)->hash($plaintext);
        ContainerFacade::get(PasswordHistoryRepositoryInterface::class)
            ->append($userId, $hash, new DateTimeImmutable());
    }
}
