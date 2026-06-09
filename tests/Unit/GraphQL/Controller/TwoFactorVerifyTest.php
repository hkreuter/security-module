<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Controller;

use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\GraphQL\Base\Service\Token;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;
use OxidEsales\SecurityModule\GraphQL\Controller\TwoFactorVerify;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TwoFactorVerifyTest extends TestCase
{
    #[Test]
    public function verifyTwoFactorTokenReturnsAccessTokenAndConsumesChallengeOnValidOtp(): void
    {
        $userId = uniqid();
        $otp = uniqid();
        $accessToken = uniqid();

        $userModelStub = $this->createStub(EshopUserModel::class);
        $userModelStub->method('getId')->willReturn($userId);
        $legacyStub = $this->createStub(Legacy::class);
        $legacyStub->method('getUserModel')->willReturn($userModelStub);

        $accessTokenStub = $this->createStub(UnencryptedToken::class);
        $accessTokenStub->method('toString')->willReturn($accessToken);
        $tokenStub = $this->createStub(Token::class);
        $tokenStub->method('getTokenClaim')->willReturnCallback(
            fn(string $claim, mixed $default = null): mixed => match ($claim) {
                'mfa_pending' => true,
                Token::CLAIM_USERID => $userId,
                default => $default,
            }
        );
        $tokenStub->method('createTokenForUser')->willReturn($accessTokenStub);

        $twoFAServiceMock = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceMock->expects($this->once())->method('verify')->with($userId, $otp);
        $twoFAServiceMock->expects($this->once())->method('consumeChallenge')->with($userId);

        $sut = $this->getSut(
            tokenService: $tokenStub,
            legacy: $legacyStub,
            twoFAService: $twoFAServiceMock,
        );

        $this->assertSame($accessToken, $sut->verifyTwoFactorToken($otp));
    }

    #[Test]
    public function verifyTwoFactorTokenThrowsAndSkipsVerifyWhenChallengeIsNotPending(): void
    {
        $tokenStub = $this->createStub(Token::class);
        $tokenStub->method('getTokenClaim')->willReturnCallback(
            fn(string $claim, mixed $default = null): mixed => $claim === 'mfa_pending' ? false : $default
        );

        $twoFAServiceMock = $this->createMock(TwoFAServiceInterface::class);
        $twoFAServiceMock->expects($this->never())->method('verify');

        $sut = $this->getSut(tokenService: $tokenStub, twoFAService: $twoFAServiceMock);

        $this->expectException(InvalidToken::class);

        $sut->verifyTwoFactorToken(uniqid());
    }

    private function getSut(
        ?Token $tokenService = null,
        ?Legacy $legacy = null,
        ?TwoFAServiceInterface $twoFAService = null,
        ?RefreshTokenServiceInterface $refreshTokenService = null,
    ): TwoFactorVerify {
        return new TwoFactorVerify(
            tokenService: $tokenService ?? $this->createStub(Token::class),
            legacy: $legacy ?? $this->createStub(Legacy::class),
            twoFAService: $twoFAService ?? $this->createStub(TwoFAServiceInterface::class),
            refreshTokenService: $refreshTokenService ?? $this->createStub(RefreshTokenServiceInterface::class),
        );
    }
}
