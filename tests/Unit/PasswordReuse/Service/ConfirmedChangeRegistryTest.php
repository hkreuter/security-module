<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Service;

use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistry;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfirmedChangeRegistryTest extends TestCase
{
    #[Test]
    public function isConfirmedReturnsTrueForAConfirmedUserId(): void
    {
        $userId = uniqid('user_', true);
        $sut = $this->getSut();

        $sut->confirm($userId);

        $this->assertTrue($sut->isConfirmed($userId));
    }

    #[Test]
    public function isConfirmedReturnsFalseForAnUnconfirmedUserId(): void
    {
        $sut = $this->getSut();

        $sut->confirm(uniqid('other_', true));

        $this->assertFalse($sut->isConfirmed(uniqid('user_', true)));
    }

    #[Test]
    public function isConfirmedReturnsFalseWhenNothingWasConfirmed(): void
    {
        $sut = $this->getSut();

        $this->assertFalse($sut->isConfirmed(uniqid('user_', true)));
    }

    #[Test]
    public function clearResetsAPreviouslyConfirmedUserId(): void
    {
        $userId = uniqid('user_', true);
        $sut = $this->getSut();
        $sut->confirm($userId);

        $sut->clear($userId);

        $this->assertFalse($sut->isConfirmed($userId));
    }

    #[Test]
    public function clearAffectsOnlyTheGivenUserId(): void
    {
        $kept = uniqid('kept_', true);
        $removed = uniqid('removed_', true);
        $sut = $this->getSut();
        $sut->confirm($kept);
        $sut->confirm($removed);

        $sut->clear($removed);

        $this->assertTrue($sut->isConfirmed($kept));
        $this->assertFalse($sut->isConfirmed($removed));
    }

    #[Test]
    public function clearOfAnUnknownUserIdIsANoOp(): void
    {
        $sut = $this->getSut();

        $sut->clear(uniqid('user_', true));

        $this->assertFalse($sut->isConfirmed(uniqid('user_', true)));
    }

    #[Test]
    public function implementsInterface(): void
    {
        $this->assertInstanceOf(ConfirmedChangeRegistryInterface::class, $this->getSut());
    }

    private function getSut(): ConfirmedChangeRegistry
    {
        return new ConfirmedChangeRegistry();
    }
}
