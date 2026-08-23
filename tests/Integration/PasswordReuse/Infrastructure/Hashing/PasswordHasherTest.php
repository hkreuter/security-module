<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Infrastructure\Hashing;

use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Hashing\PasswordHasherInterface;
use PHPUnit\Framework\Attributes\Test;

final class PasswordHasherTest extends IntegrationTestCase
{
    #[Test]
    public function hasherIsWiredBehindItsInterface(): void
    {
        $this->assertInstanceOf(
            PasswordHasherInterface::class,
            $this->get(PasswordHasherInterface::class),
        );
    }

    #[Test]
    public function verifyPasswordAcceptsThePlaintextBehindARealHash(): void
    {
        $sut = $this->get(PasswordHasherInterface::class);
        $plaintext = uniqid('secret_', true);

        $hash = $sut->hash($plaintext);

        $this->assertNotSame($plaintext, $hash);
        $this->assertTrue($sut->verifyPassword($plaintext, $hash));
    }

    #[Test]
    public function verifyPasswordRejectsAnUnrelatedPlaintext(): void
    {
        $sut = $this->get(PasswordHasherInterface::class);

        $hash = $sut->hash(uniqid('secret_', true));

        $this->assertFalse($sut->verifyPassword(uniqid('other_', true), $hash));
    }
}
