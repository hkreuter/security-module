<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Codeception\AcceptanceOXAPIStorefront;

use Codeception\Util\Fixtures;
use DateTimeImmutable;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordPolicy\Service\ModuleSettingsServiceInterface as PasswordPolicySettings;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsService as ReuseSettings;
use OxidEsales\SecurityModule\Tests\Codeception\Support\AcceptanceTester;

/**
 * Shared harness for the OXAPI storefront password-security suite.
 */
abstract class BaseCest
{
    protected const BASELINE_PASSWORD = 'Baseline-pw-2026!';

    private string $originalPasswordHash = '';
    private string $originalPasswordSalt = '';
    private string $baselineHash = '';

    protected function user(): array
    {
        return Fixtures::get('existingUser');
    }

    protected function userId(): string
    {
        return $this->user()['userId'];
    }

    protected function prepare(AcceptanceTester $I): void
    {
        $id = $this->userId();
        $this->originalPasswordHash = (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $id]);
        $this->originalPasswordSalt = (string) $I->grabFromDatabase('oxuser', 'oxpasssalt', ['oxid' => $id]);

        $this->seedBaselinePassword($I, self::BASELINE_PASSWORD);
        $this->clearHistory($I);
        $I->deleteAllEmails();
    }

    protected function cleanup(AcceptanceTester $I): void
    {
        $I->updateInDatabase(
            'oxuser',
            ['oxpassword' => $this->originalPasswordHash, 'oxpasssalt' => $this->originalPasswordSalt],
            ['oxid' => $this->userId()]
        );
        $this->clearHistory($I);
        $this->setReusePrevention(false);
        $this->setChangeNotification(false);
        $this->setPasswordPolicy(true);
        $I->deleteAllEmails();
    }

    protected function seedBaselinePassword(AcceptanceTester $I, string $plainPassword): void
    {
        $this->baselineHash = $this->hash($plainPassword);
        $I->updateInDatabase(
            'oxuser',
            ['oxpassword' => $this->baselineHash, 'oxpasssalt' => ''],
            ['oxid' => $this->userId()]
        );
    }

    protected function baselineHash(): string
    {
        return $this->baselineHash;
    }

    protected function hash(string $plainPassword): string
    {
        return ContainerFacade::get(PasswordServiceBridgeInterface::class)->hash($plainPassword);
    }

    protected function storedHash(AcceptanceTester $I): string
    {
        return (string) $I->grabFromDatabase('oxuser', 'oxpassword', ['oxid' => $this->userId()]);
    }

    protected function appendHistory(string $hash): void
    {
        ContainerFacade::get(PasswordHistoryRepositoryInterface::class)
            ->append($this->userId(), $hash, new DateTimeImmutable());
    }

    protected function clearHistory(AcceptanceTester $I): void
    {
        ContainerFacade::get(PasswordHistoryRepositoryInterface::class)->purgeForUser($this->userId());
    }

    protected function setPasswordPolicy(bool $state): void
    {
        ContainerFacade::get(PasswordPolicySettings::class)->saveIsPasswordPolicyEnabled($state);
    }

    protected function setReusePrevention(bool $state): void
    {
        ContainerFacade::get(ModuleSettingServiceInterface::class)
            ->saveBoolean(ReuseSettings::REUSE_PREVENTION_ENABLE, $state, Module::MODULE_ID);
    }

    protected function setChangeNotification(bool $state): void
    {
        ContainerFacade::get(ModuleSettingServiceInterface::class)
            ->saveBoolean(ReuseSettings::CHANGE_NOTIFICATION_ENABLE, $state, Module::MODULE_ID);
    }

    protected function loginAsUser(AcceptanceTester $I, string $password): void
    {
        $I->login($this->user()['userLoginName'], $password);
    }

    protected function sendGraphQL(AcceptanceTester $I, string $query, array $variables = []): array
    {
        $I->sendGQLQuery($query, $variables);
        $I->seeResponseIsJson();

        return $I->grabJsonResponseAsArray();
    }
}
