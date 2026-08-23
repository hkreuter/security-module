<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Shared\Controller;

use DateTimeImmutable;
use OxidEsales\Eshop\Application\Model\User;
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
use OxidEsales\SecurityModule\Shared\Controller\AccountPasswordController as ModuleAccountPasswordController;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AllowMockObjectsWithoutExpectations]
final class AccountPasswordControllerTest extends IntegrationTestCase
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
            ->onlyMethods(['getRequestParameter'])
            ->getMock();

        Registry::set(UtilsView::class, $this->utilsViewSpy);
        Registry::set(Request::class, $this->requestMock);
    }

    #[Test]
    public function changeToCurrentPasswordIsRejectedGracefullyWithoutPersistingOrThrowing(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        $userId = $this->createCustomer($currentPlain);
        $storedBefore = $this->storedHash($userId);

        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseException::MESSAGE_KEY, false, true);

        $this->submitNewPassword($userId, $currentPlain, $currentPlain, $this->guardWithRealCollection(true));

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function changeToRecentPreviousPasswordIsRejectedGracefully(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        $userId = $this->createCustomer($currentPlain);
        $previousPlain = 'previous-pw-' . uniqid();
        $this->appendHistory($userId, $previousPlain);
        $storedBefore = $this->storedHash($userId);

        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseException::MESSAGE_KEY, false, true);

        $this->submitNewPassword($userId, $currentPlain, $previousPlain, $this->guardWithRealCollection(true));

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function changeToFreshPasswordIsAllowedAndConfirmsTheChange(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        $userId = $this->createCustomer($currentPlain);

        $this->utilsViewSpy
            ->expects($this->never())
            ->method('addErrorToDisplay');

        $this->submitNewPassword($userId, $currentPlain, 'fresh-pw-' . uniqid(), $this->guardWithRealCollection(true));

        $this->assertTrue($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function genuineChangeConfirmsEvenWhenReusePreventionDisabled(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        $userId = $this->createCustomer($currentPlain);

        $this->utilsViewSpy
            ->expects($this->never())
            ->method('addErrorToDisplay');

        $this->submitNewPassword($userId, $currentPlain, 'fresh-pw-' . uniqid(), $this->guardWithRealCollection(false));

        $this->assertTrue($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function failClosedCheckErrorIsSurfacedGracefully(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        $userId = $this->createCustomer($currentPlain);
        $storedBefore = $this->storedHash($userId);

        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseCheckException::MESSAGE_KEY, false, true);

        $this->submitNewPassword($userId, $currentPlain, 'fresh-pw-' . uniqid(), $this->guardWithFailingCollection());

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    private function submitNewPassword(
        string $userId,
        string $currentPassword,
        string $newPassword,
        PasswordReuseGuardServiceInterface $guard,
    ): void {
        $this->requestMock
            ->method('getRequestParameter')
            ->willReturnCallback(fn(string $param) => match ($param) {
                'password_new', 'password_new_confirm' => $newPassword,
                'password_old' => $currentPassword,
                default => '',
            });

        $user = oxNew(User::class);
        $user->load($userId);

        $sut = $this->getSut($user, [PasswordReuseGuardServiceInterface::class => $guard]);
        $sut->changePassword();
    }

    private function getSut(User $user, array $serviceOverrides = []): ModuleAccountPasswordController
    {
        /** @var ModuleAccountPasswordController $sut */
        $sut = $this->getMockBuilder(ModuleAccountPasswordController::class)
            ->onlyMethods(['getService', 'getUser'])
            ->getMock();

        $sut->method('getUser')->willReturn($user);
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

    private function createCustomer(string $plainPassword): string
    {
        $hash = $this->container()->get(PasswordServiceBridgeInterface::class)->hash($plainPassword);
        $userId = substr(uniqid('pwacc', true), 0, 32);

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
