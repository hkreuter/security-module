<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\EventSubscriber;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\SecurityModule\GraphQL\DataType\TwoFAPendingUser;
use OxidEsales\SecurityModule\GraphQL\EventSubscriber\BeforeTokenCreationSubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BeforeTokenCreationSubscriberTest extends TestCase
{
    #[Test]
    public function stampsMfaPendingClaimWhenUserIsTwoFAPending(): void
    {
        $pendingUser = new TwoFAPendingUser($this->createStub(EshopUserModel::class));

        $eventMock = $this->createMock(BeforeTokenCreation::class);
        $eventMock->method('getUser')->willReturn($pendingUser);
        $eventMock->expects($this->once())
            ->method('withClaim')
            ->with('mfa_pending', true);

        $this->getSut()->onBeforeTokenCreation($eventMock);
    }

    #[Test]
    public function doesNotStampClaimWhenUserIsNotTwoFAPending(): void
    {
        $regularUser = $this->createStub(UserInterface::class);

        $eventMock = $this->createMock(BeforeTokenCreation::class);
        $eventMock->method('getUser')->willReturn($regularUser);
        $eventMock->expects($this->never())->method('withClaim');

        $this->getSut()->onBeforeTokenCreation($eventMock);
    }

    #[Test]
    public function subscribesToTheBeforeTokenCreationEvent(): void
    {
        $this->assertArrayHasKey(
            BeforeTokenCreation::class,
            BeforeTokenCreationSubscriber::getSubscribedEvents(),
        );
    }

    private function getSut(): BeforeTokenCreationSubscriber
    {
        return new BeforeTokenCreationSubscriber();
    }
}
