<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Subscriber;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\AfterModelUpdateEvent;
use OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\BeforeModelUpdateEvent;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordHistoryRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\StoredPasswordReaderInterface;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Time\ChangeClockInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordHistoryService;
use OxidEsales\SecurityModule\PasswordReuse\Subscriber\PasswordChangeSubscriber;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Subscriber\Double\SpyPasswordChangeNotifier;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use ReflectionObject;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PasswordChangeSubscriberObserverTest extends IntegrationTestCase
{
    private SpyPasswordChangeNotifier $notifierSpy;
    private PasswordChangeSubscriber $spySubscriber;
    private PasswordChangeSubscriber $originalSubscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->notifierSpy = new SpyPasswordChangeNotifier();
        $this->swapInSpySubscriber();
    }

    public function tearDown(): void
    {
        $dispatcher = $this->dispatcher();
        $dispatcher->removeSubscriber($this->spySubscriber);
        $dispatcher->addSubscriber($this->originalSubscriber);

        parent::tearDown();
    }

    #[Test]
    public function genuinePasswordChangeAppendsOneHistoryRowAndNotifiesOnce(): void
    {
        $userId = $this->createCustomer('current-pw-' . uniqid());

        $this->changePassword($userId, 'fresh-pw-' . uniqid());

        $this->assertSame(1, $this->historyCount($userId), 'Expected exactly one superseded-hash row.');
        $this->assertSame([$userId], $this->notifierSpy->notifiedUserIds());
    }

    #[Test]
    public function needsRehashOnlyResaveRecordsNothingAndDoesNotNotify(): void
    {
        $plainPassword = 'legacy-pw-' . uniqid();
        $userId = $this->createCustomerWithRehashableHash($plainPassword);

        $this->changePassword($userId, $plainPassword);

        $this->assertSame(0, $this->historyCount($userId));
        $this->assertSame(0, $this->notifierSpy->callCount());
    }

    #[Test]
    public function saveThatDoesNotTouchPasswordRecordsNothingAndDoesNotNotify(): void
    {
        $userId = $this->createCustomer('current-pw-' . uniqid());

        $user = oxNew(User::class);
        $user->load($userId);
        $user->assign(['oxfname' => 'Renamed-' . uniqid()]);
        $user->save();

        $this->assertSame(0, $this->historyCount($userId));
        $this->assertSame(0, $this->notifierSpy->callCount());
    }

    #[Test]
    public function confirmedGenuineChangeOnLegacyHashRecordsAndNotifiesDespiteNeedsRehash(): void
    {
        $userId = $this->createCustomerWithRehashableHash('legacy-pw-' . uniqid());

        $this->container()->get(ConfirmedChangeRegistryInterface::class)->confirm($userId);

        $this->changePassword($userId, 'fresh-pw-' . uniqid());

        $this->assertSame(1, $this->historyCount($userId));
        $this->assertSame([$userId], $this->notifierSpy->notifiedUserIds());
    }

    private function changePassword(string $userId, string $plainPassword): void
    {
        $user = oxNew(User::class);
        $user->load($userId);
        $user->setPassword($plainPassword);
        $user->save();
    }

    private function swapInSpySubscriber(): void
    {
        $container = $this->container();
        $settings = $this->enabledSettingsStub();

        $historyService = new PasswordHistoryService(
            $settings,
            $container->get(PasswordHistoryRepositoryInterface::class),
            new NullLogger(),
        );

        $this->spySubscriber = new PasswordChangeSubscriber(
            $settings,
            $container->get(ConfirmedChangeRegistryInterface::class),
            $historyService,
            $this->notifierSpy,
            $container->get(AccountTypeResolverInterface::class),
            $container->get(PasswordServiceBridgeInterface::class),
            $container->get(StoredPasswordReaderInterface::class),
            $container->get(ChangeClockInterface::class),
            new NullLogger(),
        );

        $dispatcher = $this->dispatcher();
        $this->originalSubscriber = $this->resolveRegisteredSubscriber($dispatcher);
        $dispatcher->removeSubscriber($this->originalSubscriber);
        $dispatcher->addSubscriber($this->spySubscriber);
    }

    private function enabledSettingsStub(): ModuleSettingsServiceInterface
    {
        return $this->createConfiguredStub(
            ModuleSettingsServiceInterface::class,
            [
                'isReusePreventionEnabled' => true,
                'isChangeNotificationEnabled' => true,
                'resolveCollectionSizeForRights' => 5,
            ],
        );
    }

    private function resolveRegisteredSubscriber(EventDispatcherInterface $dispatcher): PasswordChangeSubscriber
    {
        $dispatcher->getListeners(BeforeModelUpdateEvent::class);
        $dispatcher->getListeners(AfterModelUpdateEvent::class);

        $listenersProperty = (new ReflectionObject($dispatcher))->getProperty('listeners');
        foreach ($listenersProperty->getValue($dispatcher) as $listenersByPriority) {
            foreach ($listenersByPriority as $listeners) {
                foreach ($listeners as $listener) {
                    if (is_array($listener) && ($listener[0] ?? null) instanceof PasswordChangeSubscriber) {
                        return $listener[0];
                    }
                }
            }
        }

        throw new RuntimeException('The module PasswordChangeSubscriber is not registered on the dispatcher.');
    }

    private function createCustomer(string $plainPassword): string
    {
        $hash = $this->container()->get(PasswordServiceBridgeInterface::class)->hash($plainPassword);

        return $this->insertUser($hash);
    }

    private function createCustomerWithRehashableHash(string $plainPassword): string
    {
        return $this->insertUser(password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 4]));
    }

    private function insertUser(string $passwordHash): string
    {
        $userId = substr(uniqid('pwsub', true), 0, 32);

        $this->container()
            ->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection()
            ->executeStatement(
                'INSERT INTO oxuser (OXID, OXACTIVE, OXSHOPID, OXRIGHTS, OXUSERNAME, OXPASSWORD, OXPASSSALT)'
                . ' VALUES (?, 1, 1, "user", ?, ?, "")',
                [$userId, $userId . '@example.test', $passwordHash]
            );

        return $userId;
    }

    private function historyCount(string $userId): int
    {
        return $this->container()
            ->get(PasswordHistoryRepositoryInterface::class)
            ->countForUser($userId);
    }

    private function dispatcher(): EventDispatcherInterface
    {
        return $this->container()->get(EventDispatcherInterface::class);
    }

    private function container(): ContainerInterface
    {
        return ContainerFactory::getInstance()->getContainer();
    }
}
