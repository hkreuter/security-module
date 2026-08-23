<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Infrastructure\Repository;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Language;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\PasswordReuse\Exception\AccountNotFoundException;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\AccountRepositoryInterface;
use OxidEsales\SecurityModule\PasswordReuse\Service\AccountTypeResolverInterface;
use PHPUnit\Framework\Attributes\Test;

final class AccountRepositoryTest extends IntegrationTestCase
{
    #[Test]
    public function getByIdReturnsRecipientAndShopFieldsFromOxuser(): void
    {
        $email = uniqid('mail_', true) . '@example.test';
        $shopId = mt_rand(1, 9);
        $userId = $this->createUser(
            email: $email,
            rights: 'user',
            shopId: $shopId,
        );

        $affectedAccount = $this->getSut()->getById($userId);

        $this->assertSame($userId, $affectedAccount->getUserId());
        $this->assertSame($email, $affectedAccount->getEmail());
        $this->assertSame('user', $affectedAccount->getRights());
        $this->assertSame($shopId, $affectedAccount->getShopId());
    }

    #[Test]
    public function getByIdResolvesNotificationLanguageFromAccountShopNotRequestLanguage(): void
    {
        $shopId = 1;
        $shopDefaultLanguage = 1;
        $this->setShopDefaultLanguage($shopId, $shopDefaultLanguage);

        $actorRequestLanguage = 0;
        $this->get(Language::class)->setBaseLanguage($actorRequestLanguage);

        $email = uniqid('mail_', true) . '@example.test';
        $userId = $this->createUser(email: $email, rights: 'user', shopId: $shopId);

        $affectedAccount = $this->getSut()->getById($userId);

        $this->assertSame(
            $shopDefaultLanguage,
            $affectedAccount->getLanguageId(),
            'Notification language must be the affected account shop default (BR015).'
        );
        $this->assertNotSame(
            $actorRequestLanguage,
            $affectedAccount->getLanguageId(),
            'Notification language must not follow the actor request locale (BR015).'
        );
        $this->assertSame($email, $affectedAccount->getEmail());
        $this->assertSame($shopId, $affectedAccount->getShopId());
    }

    #[Test]
    public function getByIdThrowsWhenTheAccountDoesNotExist(): void
    {
        $this->expectException(AccountNotFoundException::class);

        $this->getSut()->getById(substr(uniqid('missing', true), 0, 32));
    }

    #[Test]
    public function resolverTreatsMalladminAsAdminAndUserAsCustomer(): void
    {
        $adminId = $this->createUser(rights: 'malladmin');
        $customerId = $this->createUser(rights: 'user');

        $resolver = $this->get(AccountTypeResolverInterface::class);

        $this->assertTrue($resolver->isAdmin($adminId));
        $this->assertFalse($resolver->isAdmin($customerId));
    }

    private function createUser(
        string $email = '',
        string $rights = 'user',
        int $shopId = 1,
    ): string {
        $userId = substr(uniqid('acc', true), 0, 32);

        $user = oxNew(User::class);
        $user->setId($userId);
        $user->assign([
            'oxusername' => $email !== '' ? $email : uniqid('mail_', true) . '@example.test',
            'oxactive'   => 1,
        ]);
        $user->save();

        $this->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection()
            ->executeStatement(
                'UPDATE oxuser SET OXRIGHTS = :rights, OXSHOPID = :shopId WHERE OXID = :userId',
                ['rights' => $rights, 'shopId' => $shopId, 'userId' => $userId]
            );

        return $userId;
    }

    private function setShopDefaultLanguage(int $shopId, int $languageId): void
    {
        $connection = $this->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection();

        $connection->executeStatement(
            "DELETE FROM oxconfig
             WHERE oxvarname = 'sDefaultLang' AND oxshopid = :shopId AND oxmodule = ''",
            ['shopId' => $shopId]
        );
        $connection->executeStatement(
            "INSERT INTO oxconfig (OXID, OXSHOPID, OXMODULE, OXVARNAME, OXVARTYPE, OXVARVALUE)
             VALUES (:id, :shopId, '', 'sDefaultLang', 'str', :value)",
            [
                'id'     => substr(uniqid('cfg', true), 0, 32),
                'shopId' => $shopId,
                'value'  => (string)$languageId,
            ]
        );
    }

    private function getSut(): AccountRepositoryInterface
    {
        return $this->get(AccountRepositoryInterface::class);
    }
}
