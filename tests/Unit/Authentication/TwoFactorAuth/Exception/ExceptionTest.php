<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Exception;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\MaxAttemptsExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\OTPExpiredException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\OTPValidationException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\TwoFactorAuthRequiredException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OTPValidationException::class)]
#[CoversClass(InvalidCodeException::class)]
#[CoversClass(OTPExpiredException::class)]
#[CoversClass(MaxAttemptsExceededException::class)]
#[CoversClass(TwoFactorAuthRequiredException::class)]
final class ExceptionTest extends TestCase
{
    public function testInvalidCodeExceptionExtendsOTPValidationException(): void
    {
        $exception = new InvalidCodeException();

        $this->assertInstanceOf(OTPValidationException::class, $exception);
    }

    public function testOTPExpiredExceptionExtendsOTPValidationException(): void
    {
        $exception = new OTPExpiredException();

        $this->assertInstanceOf(OTPValidationException::class, $exception);
    }

    public function testMaxAttemptsExceededExceptionExtendsOTPValidationException(): void
    {
        $exception = new MaxAttemptsExceededException();

        $this->assertInstanceOf(OTPValidationException::class, $exception);
    }

    public function testTwoFactorAuthRequiredExceptionCarriesUserId(): void
    {
        $exception = new TwoFactorAuthRequiredException('user-123');

        $this->assertSame('user-123', $exception->getUserId());
    }

    public function testTwoFactorAuthRequiredExceptionIsNotOTPValidationException(): void
    {
        $exception = new TwoFactorAuthRequiredException('user-123');

        $this->assertNotInstanceOf(OTPValidationException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
