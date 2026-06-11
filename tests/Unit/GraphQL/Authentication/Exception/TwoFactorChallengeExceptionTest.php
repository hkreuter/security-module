<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Authentication\Exception;

use Exception;
use OxidEsales\GraphQL\Base\Exception\ErrorCategories;
use OxidEsales\SecurityModule\GraphQL\Authentication\Exception\TwoFactorChallengeException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TwoFactorChallengeExceptionTest extends TestCase
{
    #[Test]
    public function carriesAGenericClientSafeMessage(): void
    {
        $sut = new TwoFactorChallengeException();

        $this->assertTrue($sut->isClientSafe(), 'Message must be surfaced to the client');
        $this->assertSame('Invalid or expired two-factor code', $sut->getMessage());
        $this->assertSame(ErrorCategories::REQUESTERROR, $sut->getCategory());
    }

    #[Test]
    public function preservesThePreviousDomainException(): void
    {
        $previous = new Exception(uniqid());

        $sut = new TwoFactorChallengeException(previous: $previous);

        $this->assertSame($previous, $sut->getPrevious());
    }
}
