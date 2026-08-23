<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Exception;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Exception\UserException;
use OxidEsales\SecurityModule\PasswordReuse\Exception\PasswordReuseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PasswordReuseExceptionTest extends TestCase
{
    #[Test]
    public function isCoreUserException(): void
    {
        $exception = new PasswordReuseException();

        $this->assertInstanceOf(UserException::class, $exception);
        $this->assertInstanceOf(StandardException::class, $exception);
    }

    #[Test]
    public function carriesRecentlyUsedMessageIdent(): void
    {
        $exception = new PasswordReuseException();

        $this->assertSame(PasswordReuseException::MESSAGE_KEY, $exception->getMessage());
    }

    #[Test]
    public function messageIdentIsNamespacedModuleConstant(): void
    {
        $this->assertStringStartsWith('OESECURITYMODULE_', PasswordReuseException::MESSAGE_KEY);
    }
}
