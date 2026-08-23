<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Service;

use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\PasswordReuse\Service\ConfirmedChangeRegistryInterface;
use PHPUnit\Framework\Attributes\Test;

final class ConfirmedChangeRegistryTest extends IntegrationTestCase
{
    #[Test]
    public function registryIsWiredBehindItsInterface(): void
    {
        $this->assertInstanceOf(
            ConfirmedChangeRegistryInterface::class,
            $this->get(ConfirmedChangeRegistryInterface::class),
        );
    }

    #[Test]
    public function registryIsASharedRequestScopedInstance(): void
    {
        $userId = uniqid('user_', true);

        $this->get(ConfirmedChangeRegistryInterface::class)->confirm($userId);

        $this->assertTrue(
            $this->get(ConfirmedChangeRegistryInterface::class)->isConfirmed($userId),
        );
    }
}
