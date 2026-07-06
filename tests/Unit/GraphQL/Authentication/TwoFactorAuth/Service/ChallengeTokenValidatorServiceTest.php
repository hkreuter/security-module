<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Authentication\TwoFactorAuth\Service;

use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Service\Token;
use OxidEsales\SecurityModule\GraphQL\Authentication\TwoFactorAuth\Service\ChallengeTokenValidatorService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ChallengeTokenValidatorServiceTest extends TestCase
{
    #[Test]
    public function validateAndGetUserIdReturnsUserIdForPendingUnexpiredChallenge(): void
    {
        $userId = uniqid();

        $sut = $this->getSut($this->tokenStub([
            'mfa_pending' => true,
            'mfa_exp' => time() + 300,
            Token::CLAIM_USERID => $userId,
        ]));

        $this->assertSame($userId, $sut->validateAndGetUserId());
    }

    #[Test]
    public function validateAndGetUserIdThrowsWhenChallengeIsNotPending(): void
    {
        $sut = $this->getSut($this->tokenStub(['mfa_pending' => false]));

        $this->expectException(InvalidToken::class);

        $sut->validateAndGetUserId();
    }

    #[Test]
    public function validateAndGetUserIdThrowsWhenChallengeExpired(): void
    {
        $sut = $this->getSut($this->tokenStub([
            'mfa_pending' => true,
            'mfa_exp' => time() - 1,
        ]));

        $this->expectException(InvalidToken::class);

        $sut->validateAndGetUserId();
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function tokenStub(array $claims): Token
    {
        $stub = $this->createStub(Token::class);
        $stub->method('getTokenClaim')->willReturnCallback(
            fn(string $claim, mixed $default = null): mixed => $claims[$claim] ?? $default
        );

        return $stub;
    }

    private function getSut(?Token $tokenService = null): ChallengeTokenValidatorService
    {
        return new ChallengeTokenValidatorService(
            tokenService: $tokenService ?? $this->createStub(Token::class),
        );
    }
}
