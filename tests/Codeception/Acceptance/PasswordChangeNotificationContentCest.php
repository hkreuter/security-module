<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\Acceptance;

use Codeception\Attribute\Group;
use DateTimeImmutable;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailContent;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

#[Group('flow:storefront-change')]
#[Group('oe_security_module')]
final class PasswordChangeNotificationContentCest extends BaseCest
{
    private const CONTENT_TABLE = 'oxcontents';
    private const FALLBACK_SUBJECT_KEY = 'OESM_PASSWORDCHANGE_EMAIL_SUBJECT';
    private const NEW_PASSWORD = 'C0ntent-fr3sh-pass!';
    private const CUSTOM_SUBJECT = 'OXDEV-10136 custom change subject';
    private const CUSTOM_BODY_MARKER = 'OXDEV-10136 custom change body marker';

    private string $originalPasswordHash = '';
    private string $originalPasswordSalt = '';
    private array $originalContent = [];

    public function _before(AcceptanceTester $I): void
    {
        $this->setCaptchaState(false);
        $this->setPasswordState(false);
        $this->setReusePreventionState(true);
        $this->setNotificationState(true);

        $userId = $this->getExistingUserData()['userId'];
        $this->originalPasswordHash = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $userId]);
        $this->originalPasswordSalt = (string) $I->grabFromDatabase('oxuser', 'oxpasssalt', ['oxid' => $userId]);

        $this->originalContent = $this->grabContentRow($I);

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

        if ($this->originalContent !== []) {
            $I->updateInDatabase(
                self::CONTENT_TABLE,
                $this->originalContent,
                ['oxloadid' => PasswordChangeMailContent::IDENT]
            );
        }

        $this->clearPasswordHistory($userData['userId']);
        $this->setReusePreventionState(false);
        $this->setNotificationState(false);

        $I->deleteAllEmails();
    }

    public function editedCmsContentDrivesSubjectAndBodyWithTimestamp(AcceptanceTester $I): void
    {
        $I->wantToTest('an admin-edited CMS content supplies the notification subject/body with the timestamp');

        $userData = $this->getExistingUserData();

        $I->updateInDatabase(
            self::CONTENT_TABLE,
            [
                'oxactive' => 1,
                'oxactive_1' => 1,
                'oxtitle' => self::CUSTOM_SUBJECT,
                'oxtitle_1' => self::CUSTOM_SUBJECT,
                'oxcontent' => self::CUSTOM_BODY_MARKER . ' am {{ changedAt }}.',
                'oxcontent_1' => self::CUSTOM_BODY_MARKER . ' on {{ changedAt }}.',
            ],
            ['oxloadid' => PasswordChangeMailContent::IDENT]
        );

        $this->submitPasswordChange($I, $userData, $userData['userPassword'], self::NEW_PASSWORD);
        $languageId = $this->accountLanguageId($userData['userId']);

        $I->openRecentEmail();
        $I->seeInEmailTo($userData['userLoginName']);
        $I->seeInEmailSubject(self::CUSTOM_SUBJECT);
        $I->seeInEmailHtmlBody(self::CUSTOM_BODY_MARKER);
        $I->assertMatchesRegularExpression($this->timestampPattern($languageId), $I->grabHtmlBodyFromEmail());
    }

    public function builtInDefaultUsedWhenCmsContentAbsent(AcceptanceTester $I): void
    {
        $I->wantToTest('the built-in translated default is used with the timestamp when no active CMS content exists');

        $userData = $this->getExistingUserData();

        $I->updateInDatabase(
            self::CONTENT_TABLE,
            ['oxactive' => 0, 'oxactive_1' => 0],
            ['oxloadid' => PasswordChangeMailContent::IDENT]
        );

        $this->submitPasswordChange($I, $userData, $userData['userPassword'], self::NEW_PASSWORD);
        $languageId = $this->accountLanguageId($userData['userId']);

        $I->openRecentEmail();
        $I->seeInEmailTo($userData['userLoginName']);
        $I->seeInEmailSubject(Registry::getLang()->translateString(self::FALLBACK_SUBJECT_KEY, $languageId, false));
        $I->assertMatchesRegularExpression($this->timestampPattern($languageId), $I->grabTextBodyFromEmail());
    }

    private function grabContentRow(AcceptanceTester $I): array
    {
        $columns = ['oxactive', 'oxactive_1', 'oxtitle', 'oxtitle_1', 'oxcontent', 'oxcontent_1'];
        $row = [];
        foreach ($columns as $column) {
            $row[$column] = (string) $I->grabFromDatabase(
                self::CONTENT_TABLE,
                $column,
                ['oxloadid' => PasswordChangeMailContent::IDENT]
            );
        }

        return $row;
    }

    private function accountLanguageId(string $userId): int
    {
        return ContainerFacade::get(AccountTypeResolverInterface::class)
            ->resolveAccount($userId)
            ->getLanguageId();
    }

    private function timestampPattern(int $languageId): string
    {
        $dateFormat = Registry::getLang()->getLanguageAbbr($languageId) === 'de' ? 'd.m.Y' : 'Y-m-d';
        $today = (new DateTimeImmutable())->setTimestamp(Registry::getUtilsDate()->getTime())->format($dateFormat);

        return '/' . preg_quote($today, '/') . ' \d{2}:\d{2}/';
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
}
