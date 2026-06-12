<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Authentication\EventSubscriber;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Event\BeforeTokenCreation;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface;
use OxidEsales\SecurityModule\GraphQL\Authentication\DataType\TwoFAPendingUser;
use OxidEsales\SecurityModule\GraphQL\Authentication\EventSubscriber\BeforeTokenCreationSubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BeforeTokenCreationSubscriberTest extends TestCase
{
    #[Test]
    public function stampsPendingAndConfiguredExpiryClaimsWhenUserIsTwoFAPending(): void
    {
        $lifetime = 120;
        $pendingUser = new TwoFAPendingUser($this->createStub(EshopUserModel::class));

        $stampedClaims = [];
        $eventMock = $this->createMock(BeforeTokenCreation::class);
        $eventMock->method('getUser')->willReturn($pendingUser);
        $eventMock->expects($this->exactly(2))
            ->method('withClaim')
            ->willReturnCallback(
                function (string $name, mixed $value) use (&$stampedClaims, $eventMock): BeforeTokenCreation {
                    $stampedClaims[$name] = $value;
                    return $eventMock;
                }
            );

        $before = time();
        $this->getSut(apiChallengeLifetime: $lifetime, otpCodeLifetime: $lifetime)
            ->onBeforeTokenCreation($eventMock);
        $after = time();

        $this->assertTrue($stampedClaims['mfa_pending']);
        // mfa_exp is now + the configured lifetime (allowing for a clock tick during the call).
        $this->assertGreaterThanOrEqual($before + $lifetime, $stampedClaims['mfa_exp']);
        $this->assertLessThanOrEqual($after + $lifetime, $stampedClaims['mfa_exp']);
    }

    #[Test]
    public function clampsExpiryToOtpCodeLifetimeWhenApiLifetimeIsLonger(): void
    {
        $apiLifetime = 600;
        $otpLifetime = 300;
        $pendingUser = new TwoFAPendingUser($this->createStub(EshopUserModel::class));

        $stampedClaims = [];
        $eventMock = $this->createMock(BeforeTokenCreation::class);
        $eventMock->method('getUser')->willReturn($pendingUser);
        $eventMock->method('withClaim')->willReturnCallback(
            function (string $name, mixed $value) use (&$stampedClaims, $eventMock): BeforeTokenCreation {
                $stampedClaims[$name] = $value;
                return $eventMock;
            }
        );

        $before = time();
        $this->getSut(apiChallengeLifetime: $apiLifetime, otpCodeLifetime: $otpLifetime)
            ->onBeforeTokenCreation($eventMock);
        $after = time();

        $this->assertGreaterThanOrEqual($before + $otpLifetime, $stampedClaims['mfa_exp']);
        $this->assertLessThanOrEqual($after + $otpLifetime, $stampedClaims['mfa_exp']);
    }

    #[Test]
    public function clampsExpiryToApiChallengeLifetimeWhenOtpLifetimeIsLonger(): void
    {
        $apiLifetime = 300;
        $otpLifetime = 600;
        $pendingUser = new TwoFAPendingUser($this->createStub(EshopUserModel::class));

        $stampedClaims = [];
        $eventMock = $this->createMock(BeforeTokenCreation::class);
        $eventMock->method('getUser')->willReturn($pendingUser);
        $eventMock->method('withClaim')->willReturnCallback(
            function (string $name, mixed $value) use (&$stampedClaims, $eventMock): BeforeTokenCreation {
                $stampedClaims[$name] = $value;
                return $eventMock;
            }
        );

        $before = time();
        $this->getSut(apiChallengeLifetime: $apiLifetime, otpCodeLifetime: $otpLifetime)
            ->onBeforeTokenCreation($eventMock);
        $after = time();

        $this->assertGreaterThanOrEqual($before + $apiLifetime, $stampedClaims['mfa_exp']);
        $this->assertLessThanOrEqual($after + $apiLifetime, $stampedClaims['mfa_exp']);
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

    private function getSut(
        int $apiChallengeLifetime = 300,
        int $otpCodeLifetime = 300,
    ): BeforeTokenCreationSubscriber {
        $settingsStub = $this->createStub(TwoFAShopSettingsInterface::class);
        $settingsStub->method('getApiChallengeLifetime')->willReturn($apiChallengeLifetime);
        $settingsStub->method('getOtpCodeLifetime')->willReturn($otpCodeLifetime);

        return new BeforeTokenCreationSubscriber(settings: $settingsStub);
    }
}
