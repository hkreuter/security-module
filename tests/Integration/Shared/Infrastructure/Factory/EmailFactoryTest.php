<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Shared\Infrastructure\Factory;

use OxidEsales\Eshop\Core\Email;
use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\EmailFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EmailFactoryTest extends TestCase
{
    #[Test]
    public function createReturnsAnEmailModel(): void
    {
        $emailFactory = new EmailFactory();

        $this->assertInstanceOf(Email::class, $emailFactory->create());
    }

    #[Test]
    public function eachCreateReturnsADistinctEmailModel(): void
    {
        $emailFactory = new EmailFactory();

        $emailModel = $emailFactory->create();
        $newEmailModel = $emailFactory->create();

        $this->assertInstanceOf(Email::class, $emailModel);
        $this->assertInstanceOf(Email::class, $newEmailModel);
        $this->assertNotSame($emailModel, $newEmailModel);
    }
}
