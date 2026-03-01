<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\DTO;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use PHPUnit\Framework\TestCase;

final class OTPTest extends TestCase
{
    private OTP $otp;
    private \DateTimeImmutable $expiresAt;
    private \DateTimeImmutable $lastSentAt;

    protected function setUp(): void
    {
        $this->expiresAt = new \DateTimeImmutable('2026-03-01 12:05:00');
        $this->lastSentAt = new \DateTimeImmutable('2026-03-01 12:00:00');

        $this->otp = new OTP(
            userId: 'user123',
            code: '123456',
            expiresAt: $this->expiresAt,
            attempts: 2,
            lastSentAt: $this->lastSentAt,
            sid: 'session-abc',
            context: 'frontend',
        );
    }

    public function testGetUserId(): void
    {
        $this->assertSame('user123', $this->otp->getUserId());
    }

    public function testGetCode(): void
    {
        $this->assertSame('123456', $this->otp->getCode());
    }

    public function testGetExpiresAt(): void
    {
        $this->assertSame($this->expiresAt, $this->otp->getExpiresAt());
    }

    public function testGetAttempts(): void
    {
        $this->assertSame(2, $this->otp->getAttempts());
    }

    public function testGetLastSentAt(): void
    {
        $this->assertSame($this->lastSentAt, $this->otp->getLastSentAt());
    }

    public function testGetLastSentAtNull(): void
    {
        $otp = new OTP(
            userId: 'user123',
            code: '123456',
            expiresAt: $this->expiresAt,
            attempts: 0,
            lastSentAt: null,
            sid: 'session-abc',
            context: 'frontend',
        );

        $this->assertNull($otp->getLastSentAt());
    }

    public function testGetSid(): void
    {
        $this->assertSame('session-abc', $this->otp->getSid());
    }

    public function testGetContext(): void
    {
        $this->assertSame('frontend', $this->otp->getContext());
    }
}
