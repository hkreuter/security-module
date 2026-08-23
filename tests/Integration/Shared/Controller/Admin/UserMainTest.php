<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Shared\Controller\Admin;

use DateTimeImmutable;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing\PasswordHasherInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionService;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardService;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;
use OxidEsales\SecurityModule\Shared\Controller\Admin\UserMain as ModuleUserMain;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AllowMockObjectsWithoutExpectations]
final class UserMainTest extends IntegrationTestCase
{
    private UtilsView $utilsViewSpy;
    private Request $requestMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->utilsViewSpy = $this->getMockBuilder(UtilsView::class)
            ->onlyMethods(['addErrorToDisplay'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequestEscapedParameter'])
            ->getMock();

        Registry::set(UtilsView::class, $this->utilsViewSpy);
        Registry::set(Request::class, $this->requestMock);
    }

    public function tearDown(): void
    {
        $config = Registry::getConfig();
        $config->setConfigParam('blAdmin', false);
        $config->setAdminMode(false);

        Registry::set(Request::class, null);
        Registry::set(UtilsView::class, null);

        parent::tearDown();
    }

    #[Test]
    public function reuseOfCurrentPasswordOnExistingUserIsRejectedGracefullyWithoutPersisting(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        $userId = $this->createUser($currentPlain);
        $storedBefore = $this->storedHash($userId);

        $this->stubRequest($userId, $currentPlain);
        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseException::MESSAGE_KEY, false, true);

        $sut = $this->getSut($userId, true, [
            PasswordReuseGuardServiceInterface::class => $this->guardWithRealCollection(true),
        ]);
        $sut->save();

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function reuseOfRecentPreviousPasswordOnExistingUserIsRejectedGracefully(): void
    {
        $userId = $this->createUser('current-pw-' . uniqid());
        $previousPlain = 'previous-pw-' . uniqid();
        $this->appendHistory($userId, $previousPlain);
        $storedBefore = $this->storedHash($userId);

        $this->stubRequest($userId, $previousPlain);
        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseException::MESSAGE_KEY, false, true);

        $sut = $this->getSut($userId, true, [
            PasswordReuseGuardServiceInterface::class => $this->guardWithRealCollection(true),
        ]);
        $sut->save();

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function failClosedCheckErrorOnExistingUserIsSurfacedGracefully(): void
    {
        $userId = $this->createUser('current-pw-' . uniqid());
        $storedBefore = $this->storedHash($userId);

        $this->stubRequest($userId, 'fresh-pw-' . uniqid());
        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseCheckException::MESSAGE_KEY, false, true);

        $sut = $this->getSut($userId, true, [
            PasswordReuseGuardServiceInterface::class => $this->guardWithFailingCollection(),
        ]);
        $sut->save();

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function freshPasswordOnExistingUserIsAllowedConfirmsAndPersists(): void
    {
        $userId = $this->createUser('current-pw-' . uniqid());
        $storedBefore = $this->storedHash($userId);

        $this->stubRequest($userId, 'fresh-pw-' . uniqid());
        $this->utilsViewSpy
            ->expects($this->never())
            ->method('addErrorToDisplay');

        $sut = $this->getSut($userId, true, [
            PasswordReuseGuardServiceInterface::class => $this->guardWithRealCollection(true),
        ]);
        $sut->save();

        $this->assertNotSame($storedBefore, $this->storedHash($userId), 'A fresh password must be applied.');
        $this->assertTrue($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function genuineChangeConfirmsEvenWhenReusePreventionDisabled(): void
    {
        $userId = $this->createUser('current-pw-' . uniqid());

        $this->stubRequest($userId, 'fresh-pw-' . uniqid());
        $this->utilsViewSpy
            ->expects($this->never())
            ->method('addErrorToDisplay');

        $sut = $this->getSut($userId, true, [
            PasswordReuseGuardServiceInterface::class => $this->guardWithRealCollection(false),
        ]);
        $sut->save();

        $this->assertTrue($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function creatingANewUserIsExemptFromGuardAndConfirm(): void
    {
        $this->stubRequest('-1', 'provisioned-pw-' . uniqid());

        $guard = $this->createMock(PasswordReuseGuardServiceInterface::class);
        $guard->expects($this->never())->method('guardChange');
        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $sut = $this->getSut('-1', false, [
            PasswordReuseGuardServiceInterface::class => $guard,
            ConfirmedChangeRegistryInterface::class => $registry,
        ]);
        $sut->save();
    }

    #[Test]
    public function blankPasswordFieldIsNotAChangeAndIsExempt(): void
    {
        $userId = $this->createUser('current-pw-' . uniqid());
        $this->stubRequest($userId, '');

        $guard = $this->createMock(PasswordReuseGuardServiceInterface::class);
        $guard->expects($this->never())->method('guardChange');
        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $sut = $this->getSut($userId, false, [
            PasswordReuseGuardServiceInterface::class => $guard,
            ConfirmedChangeRegistryInterface::class => $registry,
        ]);
        $sut->save();
    }

    #[Test]
    public function adminProvisionedAccountWithEmptyStoredPasswordIsExempt(): void
    {
        $userId = $this->createUser('');
        $this->stubRequest($userId, 'provisioned-pw-' . uniqid());

        $guard = $this->createMock(PasswordReuseGuardServiceInterface::class);
        $guard->expects($this->never())->method('guardChange');
        $registry = $this->createMock(ConfirmedChangeRegistryInterface::class);
        $registry->expects($this->never())->method('confirm');

        $sut = $this->getSut($userId, false, [
            PasswordReuseGuardServiceInterface::class => $guard,
            ConfirmedChangeRegistryInterface::class => $registry,
        ]);
        $sut->save();
    }

    private function stubRequest(string $userId, string $newPassword): void
    {
        $editval = [
            'oxuser__oxusername' => $userId . '@example.test',
            'oxuser__oxrights' => 'user',
            'oxuser__oxactive' => 1,
        ];

        $this->requestMock
            ->method('getRequestEscapedParameter')
            ->willReturnCallback(
                fn(string $param, $default = null) => match ($param) {
                    'newPassword' => $newPassword,
                    'editval' => $editval,
                    default => $default ?? '',
                }
            );
    }

    private function getSut(
        string $editObjectId,
        bool $allowAdminEdit,
        array $serviceOverrides = [],
    ): ModuleUserMain {
        /** @var ModuleUserMain $sut */
        $sut = $this->getMockBuilder(ModuleUserMain::class)
            ->onlyMethods(['getService', 'getEditObjectId', 'allowAdminEdit', 'resetContentCache'])
            ->getMock();

        $sut->method('getEditObjectId')->willReturn($editObjectId);
        $sut->method('allowAdminEdit')->willReturn($allowAdminEdit);
        $sut->method('getService')->willReturnCallback(
            fn(string $id) => $serviceOverrides[$id] ?? $this->container()->get($id)
        );

        return $sut;
    }

    private function guardWithRealCollection(bool $reuseEnabled): PasswordReuseGuardService
    {
        $settings = $this->settingsStub($reuseEnabled);

        $collection = new PasswordCollectionService(
            $this->container()->get(PasswordHasherInterface::class),
            $this->container()->get(PasswordHistoryRepositoryInterface::class),
            $settings,
            $this->container()->get(AccountTypeResolverInterface::class),
        );

        return new PasswordReuseGuardService(
            $collection,
            $settings,
            $this->changeRegistry(),
            new NullLogger(),
        );
    }

    private function guardWithFailingCollection(): PasswordReuseGuardService
    {
        $collection = $this->createStub(PasswordCollectionServiceInterface::class);
        $collection->method('isCandidateInCollection')
            ->willThrowException(new PasswordReuseCheckException());

        return new PasswordReuseGuardService(
            $collection,
            $this->settingsStub(true),
            $this->changeRegistry(),
            new NullLogger(),
        );
    }

    private function settingsStub(bool $reuseEnabled): ModuleSettingsServiceInterface
    {
        return $this->createConfiguredStub(
            ModuleSettingsServiceInterface::class,
            [
                'isReusePreventionEnabled' => $reuseEnabled,
                'resolveCollectionSizeForRights' => 5,
            ],
        );
    }

    private function createUser(string $plainPassword): string
    {
        $hash = $plainPassword === ''
            ? ''
            : $this->container()->get(PasswordServiceBridgeInterface::class)->hash($plainPassword);
        $userId = substr(uniqid('pwadmin', true), 0, 32);

        $this->connection()->executeStatement(
            'INSERT INTO oxuser (OXID, OXACTIVE, OXSHOPID, OXRIGHTS, OXUSERNAME, OXPASSWORD, OXPASSSALT)'
            . ' VALUES (?, 1, 1, "user", ?, ?, "")',
            [$userId, $userId . '@example.test', $hash]
        );

        return $userId;
    }

    private function appendHistory(string $userId, string $plainPassword): void
    {
        $hash = $this->container()->get(PasswordServiceBridgeInterface::class)->hash($plainPassword);
        $this->container()->get(PasswordHistoryRepositoryInterface::class)
            ->append($userId, $hash, new DateTimeImmutable());
    }

    private function storedHash(string $userId): string
    {
        return (string)$this->container()->get(StoredPasswordReaderInterface::class)
            ->getStoredPasswordHash($userId);
    }

    private function changeRegistry(): ConfirmedChangeRegistryInterface
    {
        return $this->container()->get(ConfirmedChangeRegistryInterface::class);
    }

    private function connection(): \Doctrine\DBAL\Connection
    {
        return $this->container()->get(QueryBuilderFactoryInterface::class)->create()->getConnection();
    }

    private function container(): ContainerInterface
    {
        return ContainerFactory::getInstance()->getContainer();
    }
}
