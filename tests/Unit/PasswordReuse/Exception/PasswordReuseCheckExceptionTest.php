<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Exception;

use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseCheckException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PasswordReuseCheckExceptionTest extends TestCase
{
    #[Test]
    public function isRuntimeException(): void
    {
        $exception = new PasswordReuseCheckException();

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    #[Test]
    public function isDistinctFromReuseRejection(): void
    {
        $exception = new PasswordReuseCheckException();

        $this->assertNotInstanceOf(PasswordReuseException::class, $exception);
        $this->assertNotInstanceOf(UserException::class, $exception);
    }

    #[Test]
    public function carriesFailClosedMessageIdent(): void
    {
        $exception = new PasswordReuseCheckException();

        $this->assertSame(PasswordReuseCheckException::MESSAGE_KEY, $exception->getMessage());
        $this->assertStringStartsWith('OESECURITYMODULE_', PasswordReuseCheckException::MESSAGE_KEY);
    }

    #[Test]
    public function preservesUnderlyingCause(): void
    {
        $cause = new RuntimeException('table unavailable');

        $exception = new PasswordReuseCheckException($cause);

        $this->assertSame($cause, $exception->getPrevious());
    }
}
