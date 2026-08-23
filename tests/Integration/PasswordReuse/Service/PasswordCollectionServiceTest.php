<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Service;

use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\PasswordReuse\Service\PasswordCollectionServiceInterface;
use PHPUnit\Framework\Attributes\Test;

final class PasswordCollectionServiceTest extends IntegrationTestCase
{
    #[Test]
    public function serviceIsWiredBehindItsInterface(): void
    {
        $this->assertInstanceOf(
            PasswordCollectionServiceInterface::class,
            $this->get(PasswordCollectionServiceInterface::class),
        );
    }
}
