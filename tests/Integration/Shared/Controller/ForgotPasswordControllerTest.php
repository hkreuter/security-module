<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Shared\Controller;

use DateTimeImmutable;
use Generator;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsView;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\SecurityModule\Captcha\Service\CaptchaServiceInterface;
use OxidEsales\SecurityModule\Captcha\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing\PasswordHasherInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface as ReuseSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionService;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardService;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordReuseGuardServiceInterface;
use OxidEsales\SecurityModule\Shared\Controller\ForgotPasswordController as ModuleForgotPasswordController;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
class ForgotPasswordControllerTest extends IntegrationTestCase
{
    protected UtilsView $utilsViewSpy;
    protected Request $requestMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->utilsViewSpy = $this->createMock(UtilsView::class);

        $this->requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequestParameter'])
            ->getMock();

        Registry::set(UtilsView::class, $this->utilsViewSpy);
        Registry::set(Request::class, $this->requestMock);
    }

    #[Test]
    public function forgotPasswordWithValidCaptcha(): void
    {
        $this->expectNotToPerformAssertions();

        $this->requestMock
            ->method('getRequestParameter')
            ->willReturnCallback(function ($param) {
                return '';
            });

        $this->utilsViewSpy
            ->expects($this->any())
            ->method('addErrorToDisplay')
            ->willReturnCallback(function ($message) {
                $this->assertNotEquals('ERROR_INVALID_CAPTCHA', $message);
            });

        $subject = $this->getSut();
        $subject->forgotPassword();
    }

    #[Test]
    #[DataProvider('forgotPasswordExceptionCasesDataProvider')]
    public function forgotPasswordDisplaysErrorOnCaptchaException(string $errorCode): void
    {
        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with($errorCode);

        $captchaService = $this->createMock(CaptchaServiceInterface::class);
        $captchaService->method('validate')->willThrowException(new StandardException($errorCode));

        $subject = $this->getSut([CaptchaServiceInterface::class => $captchaService]);
        $subject->forgotPassword();
    }

    public static function forgotPasswordExceptionCasesDataProvider(): Generator
    {
        yield 'invalid captcha' => ['ERROR_INVALID_CAPTCHA'];
        yield 'honey pot captcha' => ['FORM_VALIDATION_FAILED'];
        yield 'empty captcha' => ['ERROR_EMPTY_CAPTCHA'];
    }

    #[Test]
    public function reuseRejectionInResetPreservesTokenAndLeavesPasswordUnchanged(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        [$userId, $updateId] = $this->createUserWithResetToken($currentPlain);
        $storedBefore = $this->storedHash($userId);

        $this->stubRequestPasswords($currentPlain, $currentPlain);
        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseException::MESSAGE_KEY, false, true);

        $sut = $this->getUpdatePasswordSut(
            $updateId,
            [PasswordReuseGuardServiceInterface::class => $this->guardWithRealCollection(true)]
        );
        $sut->updatePassword();

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertTrue($this->resetTokenStillValid($updateId), 'The reset token must survive a reuse rejection.');
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function failClosedRejectionInResetPreservesTokenAndLeavesPasswordUnchanged(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        [$userId, $updateId] = $this->createUserWithResetToken($currentPlain);
        $storedBefore = $this->storedHash($userId);

        $freshPlain = 'Fresh-Pw-' . uniqid();
        $this->stubRequestPasswords($freshPlain, $freshPlain);
        $this->utilsViewSpy
            ->expects($this->once())
            ->method('addErrorToDisplay')
            ->with(PasswordReuseCheckException::MESSAGE_KEY, false, true);

        $sut = $this->getUpdatePasswordSut(
            $updateId,
            [PasswordReuseGuardServiceInterface::class => $this->guardWithFailingCollection()]
        );
        $sut->updatePassword();

        $this->assertSame($storedBefore, $this->storedHash($userId), 'The stored password must be unchanged.');
        $this->assertTrue(
            $this->resetTokenStillValid($updateId),
            'The reset token must survive a fail-closed rejection.'
        );
        $this->assertFalse($this->changeRegistry()->isConfirmed($userId));
    }

    #[Test]
    public function freshPasswordInResetChangesPasswordConfirmsChangeAndConsumesToken(): void
    {
        $currentPlain = 'current-pw-' . uniqid();
        [$userId, $updateId] = $this->createUserWithResetToken($currentPlain);
        $storedBefore = $this->storedHash($userId);

        $freshPlain = 'Fresh-Pw-' . uniqid();
        $this->stubRequestPasswords($freshPlain, $freshPlain);
        $this->utilsViewSpy
            ->expects($this->never())
            ->method('addErrorToDisplay');

        $sut = $this->getUpdatePasswordSut(
            $updateId,
            [PasswordReuseGuardServiceInterface::class => $this->guardWithRealCollection(true)]
        );
        $sut->updatePassword();

        $this->assertNotSame($storedBefore, $this->storedHash($userId), 'A fresh password must be applied.');
        $this->assertTrue($this->changeRegistry()->isConfirmed($userId));
        $this->assertFalse($this->resetTokenStillValid($updateId), 'A successful reset must consume the token.');
    }

    private function stubRequestPasswords(string $newPassword, string $confirmPassword): void
    {
        $this->requestMock
            ->method('getRequestParameter')
            ->willReturnCallback(
                fn(string $param) => match ($param) {
                    'password_new' => $newPassword,
                    'password_new_confirm' => $confirmPassword,
                    default => '',
                }
            );
    }

    private function getSut(array $serviceOverrides = []): ModuleForgotPasswordController
    {
        $services = array_merge(
            [
                ModuleSettingsServiceInterface::class => $this->createConfiguredStub(
                    ModuleSettingsServiceInterface::class,
                    ['isCaptchaEnabled' => true]
                ),
                CaptchaServiceInterface::class => $this->createStub(CaptchaServiceInterface::class),
            ],
            $serviceOverrides
        );

        /** @var ModuleForgotPasswordController $sut */
        $sut = $this->getMockBuilder(ModuleForgotPasswordController::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $sut->method('getService')->willReturnCallback(
            fn(string $id) => $services[$id] ?? ContainerFacade::get($id)
        );

        return $sut;
    }

    private function getUpdatePasswordSut(
        string $updateId,
        array $serviceOverrides = [],
    ): ModuleForgotPasswordController {
        /** @var ModuleForgotPasswordController $sut */
        $sut = $this->getMockBuilder(ModuleForgotPasswordController::class)
            ->onlyMethods(['getService', 'getUpdateId'])
            ->getMock();

        $sut->method('getUpdateId')->willReturn($updateId);
        $sut->method('getService')->willReturnCallback(
            fn(string $id) => $serviceOverrides[$id] ?? ContainerFacade::get($id)
        );

        return $sut;
    }

    private function guardWithRealCollection(bool $reuseEnabled): PasswordReuseGuardService
    {
        $settings = $this->reuseSettingsStub($reuseEnabled);

        $collection = new PasswordCollectionService(
            ContainerFacade::get(PasswordHasherInterface::class),
            ContainerFacade::get(PasswordHistoryRepositoryInterface::class),
            $settings,
            ContainerFacade::get(AccountTypeResolverInterface::class),
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
            $this->reuseSettingsStub(true),
            $this->changeRegistry(),
            new NullLogger(),
        );
    }

    private function reuseSettingsStub(bool $reuseEnabled): ReuseSettingsServiceInterface
    {
        return $this->createConfiguredStub(
            ReuseSettingsServiceInterface::class,
            [
                'isReusePreventionEnabled' => $reuseEnabled,
                'resolveCollectionSizeForRights' => 5,
            ],
        );
    }

    /**
     * @return array{0: string, 1: string} the user id and the matching reset update id (token)
     */
    private function createUserWithResetToken(string $plainPassword): array
    {
        $hash = ContainerFacade::get(PasswordServiceBridgeInterface::class)->hash($plainPassword);
        $userId = substr(uniqid('pwreset', true), 0, 32);
        $updateKey = substr(uniqid('key', true), 0, 32);
        $updateExp = time() + 3600;

        $this->connection()->executeStatement(
            'INSERT INTO oxuser'
            . ' (OXID, OXACTIVE, OXSHOPID, OXRIGHTS, OXUSERNAME, OXPASSWORD, OXPASSSALT, OXUPDATEKEY, OXUPDATEEXP)'
            . ' VALUES (?, 1, 1, "user", ?, ?, "", ?, ?)',
            [$userId, $userId . '@example.test', $hash, $updateKey, $updateExp]
        );

        $updateId = md5($userId . '1' . $updateKey);

        return [$userId, $updateId];
    }

    private function resetTokenStillValid(string $updateId): bool
    {
        $count = $this->connection()->fetchOne(
            'SELECT 1 FROM oxuser'
            . ' WHERE oxupdateexp >= ? AND MD5(CONCAT(oxid, oxshopid, oxupdatekey)) = ?',
            [time(), $updateId]
        );

        return $count !== false;
    }

    private function storedHash(string $userId): string
    {
        return (string)ContainerFacade::get(StoredPasswordReaderInterface::class)
            ->getStoredPasswordHash($userId);
    }

    private function changeRegistry(): ConfirmedChangeRegistryInterface
    {
        return ContainerFacade::get(ConfirmedChangeRegistryInterface::class);
    }

    private function connection(): \Doctrine\DBAL\Connection
    {
        return ContainerFactory::getInstance()->getContainer()
            ->get(QueryBuilderFactoryInterface::class)->create()->getConnection();
    }
}
