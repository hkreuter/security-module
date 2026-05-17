<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\OTP;

use DateTimeImmutable;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\Factory\OtpNotifierFactoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\OtpNotifierInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\OtpFacade;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpChallengeStateServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeGeneratorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeValidatorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\DTO\OtpChallengeStateInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpSendPolicyServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\AttemptLimitExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\ResendCooldownException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\InvalidCodeException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OtpFacadeTest extends TestCase
{
    #[Test]
    public function isVerifiedReturnsFalseWhenNoChallengeState(): void
    {
        $stateServiceMock = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceMock->expects($this->once())
            ->method('getChallengeState')
            ->with($userId = uniqid())
            ->willReturn(null);

        $sut = $this->getSut(stateService: $stateServiceMock);

        $this->assertFalse($sut->isVerified(userId: $userId));
    }

    #[Test]
    public function isVerifiedReturnsFalseWhenVerifiedAtIsNull(): void
    {
        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getVerifiedAt')->willReturn(null);

        $stateServiceMock = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceMock->expects($this->once())
            ->method('getChallengeState')
            ->with($userId = uniqid())
            ->willReturn($stateStub);

        $sut = $this->getSut(stateService: $stateServiceMock);

        $this->assertFalse($sut->isVerified(userId: $userId));
    }

    #[Test]
    public function isVerifiedReturnsFalseWhenExpired(): void
    {
        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getVerifiedAt')->willReturn(new DateTimeImmutable());
        $stateStub->method('getExpiresAt')->willReturn(new DateTimeImmutable('-1 second'));

        $stateServiceMock = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceMock->expects($this->once())
            ->method('getChallengeState')
            ->with($userId = uniqid())
            ->willReturn($stateStub);

        $sut = $this->getSut(stateService: $stateServiceMock);

        $this->assertFalse($sut->isVerified(userId: $userId));
    }

    #[Test]
    public function isVerifiedReturnsTrueWhenVerifiedAtIsSet(): void
    {
        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getVerifiedAt')->willReturn(new DateTimeImmutable());
        $stateStub->method('getExpiresAt')->willReturn(new DateTimeImmutable('+5 minutes'));

        $stateServiceMock = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceMock->expects($this->once())
            ->method('getChallengeState')
            ->with($userId = uniqid())
            ->willReturn($stateStub);

        $sut = $this->getSut(stateService: $stateServiceMock);

        $this->assertTrue($sut->isVerified(userId: $userId));
    }

    #[Test]
    public function invalidateChallengeDeletesChallengeState(): void
    {
        $stateServiceSpy = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceSpy->expects($this->once())
            ->method('deleteChallengeState')
            ->with($userId = uniqid());

        $sut = $this->getSut(stateService: $stateServiceSpy);

        $sut->invalidateChallenge(userId: $userId);
    }

    #[Test]
    public function verifyTriggersCodeValidator(): void
    {
        $codeValidatorSpy = $this->createMock(OtpCodeValidatorServiceInterface::class);
        $codeValidatorSpy->expects($this->once())
            ->method('validateCode')
            ->with($userId = uniqid(), $code = uniqid());

        $sut = $this->getSut(codeValidator: $codeValidatorSpy);

        $sut->verify(userId: $userId, code: $code);
    }

    #[Test]
    public function verifyMarksChallengAsVerifiedIfValidationOk(): void
    {
        $stateServiceSpy = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceSpy->expects($this->once())
            ->method('markVerified')
            ->with($userId = uniqid());

        $sut = $this->getSut(stateService: $stateServiceSpy);

        $sut->verify(userId: $userId, code: uniqid());
    }

    #[Test]
    public function verifyDoesNotMarkVerifiedWhenValidationFails(): void
    {
        $codeValidatorStub = $this->createStub(OtpCodeValidatorServiceInterface::class);
        $codeValidatorStub->method('validateCode')->willThrowException(new InvalidCodeException());

        $stateServiceSpy = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceSpy->expects($this->never())
            ->method('markVerified');

        $sut = $this->getSut(
            stateService: $stateServiceSpy,
            codeValidator: $codeValidatorStub,
        );

        $this->expectException(InvalidCodeException::class);

        $sut->verify(userId: uniqid(), code: uniqid());
    }

    #[Test]
    public function triggerChallengeGeneratesCodeCreatesStateAndNotifies(): void
    {
        $sendPolicyStub = $this->createStub(OtpSendPolicyServiceInterface::class);
        $sendPolicyStub->method('canSend')->willReturn(true);

        $codeGeneratorStub = $this->createStub(OtpCodeGeneratorServiceInterface::class);
        $codeGeneratorStub->method('generateCode')->willReturn($code = uniqid());

        $stateServiceSpy = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceSpy->expects($this->once())
            ->method('createChallengeState')
            ->with($userId = uniqid(), $code);

        $notifierSpy = $this->createMock(OtpNotifierInterface::class);
        $notifierSpy->expects($this->once())
            ->method('notify')
            ->with($userId, $code);

        $notifierFactoryStub = $this->createStub(OtpNotifierFactoryInterface::class);
        $notifierFactoryStub->method('create')->willReturn($notifierSpy);

        $sut = $this->getSut(
            stateService: $stateServiceSpy,
            codeGenerator: $codeGeneratorStub,
            notifierFactory: $notifierFactoryStub,
            sendPolicy: $sendPolicyStub,
        );

        $sut->triggerChallenge(userId: $userId);
    }

    #[Test]
    public function triggerChallengeDoesNothingWhenCooldownActive(): void
    {
        $sendPolicyStub = $this->createStub(OtpSendPolicyServiceInterface::class);
        $sendPolicyStub->method('canSend')->willReturn(false);

        $stateServiceSpy = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceSpy->expects($this->never())->method('createChallengeState');

        $notifierFactorySpy = $this->createMock(OtpNotifierFactoryInterface::class);
        $notifierFactorySpy->expects($this->never())->method('create');

        $sut = $this->getSut(
            stateService: $stateServiceSpy,
            notifierFactory: $notifierFactorySpy,
            sendPolicy: $sendPolicyStub,
        );

        $sut->triggerChallenge(userId: uniqid());
    }

    #[Test]
    public function resendThrowsAttemptLimitExceededWhenLockedOut(): void
    {
        $userId = uniqid();

        $sendPolicyStub = $this->createStub(OtpSendPolicyServiceInterface::class);
        $sendPolicyStub->method('canSend')->willReturn(true);

        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getAttempts')->willReturn(5);

        $stateServiceStub = $this->createStub(OtpChallengeStateServiceInterface::class);
        $stateServiceStub->method('getChallengeState')->willReturn($stateStub);

        $codeValidatorStub = $this->createStub(OtpCodeValidatorServiceInterface::class);
        $codeValidatorStub->method('getMaxAttempts')->willReturn(5);

        $sut = $this->getSut(
            stateService: $stateServiceStub,
            codeValidator: $codeValidatorStub,
            sendPolicy: $sendPolicyStub,
        );

        $this->expectException(AttemptLimitExceededException::class);

        $sut->resend(userId: $userId);
    }

    #[Test]
    public function resendThrowsCooldownExceptionWhenSendNotAllowed(): void
    {
        $sendPolicyMock = $this->createMock(OtpSendPolicyServiceInterface::class);
        $sendPolicyMock->method('canSend')
            ->with($userId = uniqid())
            ->willReturn(false);

        $sut = $this->getSut(sendPolicy: $sendPolicyMock);

        $this->expectException(ResendCooldownException::class);

        $sut->resend(userId: $userId);
    }

    #[Test]
    public function resendRefreshesStateAndNotifiesWhenAllowed(): void
    {
        $sendPolicyMock = $this->createMock(OtpSendPolicyServiceInterface::class);
        $sendPolicyMock->method('canSend')
            ->with($userId = uniqid())
            ->willReturn(true);

        $codeGeneratorStub = $this->createStub(OtpCodeGeneratorServiceInterface::class);
        $codeGeneratorStub->method('generateCode')
            ->willReturn($code = uniqid());

        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getAttempts')->willReturn(2);

        $stateServiceSpy = $this->createMock(OtpChallengeStateServiceInterface::class);
        $stateServiceSpy->method('getChallengeState')->willReturn($stateStub);
        $stateServiceSpy->expects($this->once())
            ->method('refreshChallengeState')
            ->with($userId, $code);

        $codeValidatorStub = $this->createStub(OtpCodeValidatorServiceInterface::class);
        $codeValidatorStub->method('getMaxAttempts')->willReturn(5);

        $notifierSpy = $this->createMock(OtpNotifierInterface::class);
        $notifierSpy->expects($this->once())
            ->method('notify')
            ->with($userId, $code);

        $notifierFactoryStub = $this->createStub(OtpNotifierFactoryInterface::class);
        $notifierFactoryStub->method('create')->willReturn($notifierSpy);

        $sut = $this->getSut(
            stateService: $stateServiceSpy,
            codeValidator: $codeValidatorStub,
            codeGenerator: $codeGeneratorStub,
            notifierFactory: $notifierFactoryStub,
            sendPolicy: $sendPolicyMock,
        );

        $sut->resend(userId: $userId);
    }

    #[Test]
    public function getRemainingAttemptsReturnsMaxWhenNoChallengeState(): void
    {
        $stateServiceStub = $this->createStub(OtpChallengeStateServiceInterface::class);
        $stateServiceStub->method('getChallengeState')->willReturn(null);

        $codeValidatorStub = $this->createStub(OtpCodeValidatorServiceInterface::class);
        $codeValidatorStub->method('getMaxAttempts')->willReturn(5);

        $sut = $this->getSut(stateService: $stateServiceStub, codeValidator: $codeValidatorStub);

        $this->assertSame(5, $sut->getRemainingAttempts(uniqid()));
    }

    #[Test]
    public function getRemainingAttemptsSubtractsCurrentAttempts(): void
    {
        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getAttempts')->willReturn(3);

        $stateServiceStub = $this->createStub(OtpChallengeStateServiceInterface::class);
        $stateServiceStub->method('getChallengeState')->willReturn($stateStub);

        $codeValidatorStub = $this->createStub(OtpCodeValidatorServiceInterface::class);
        $codeValidatorStub->method('getMaxAttempts')->willReturn(5);

        $sut = $this->getSut(stateService: $stateServiceStub, codeValidator: $codeValidatorStub);

        $this->assertSame(2, $sut->getRemainingAttempts(uniqid()));
    }

    #[Test]
    public function getCooldownRemainingDelegatesToSendPolicy(): void
    {
        $sendPolicyMock = $this->createMock(OtpSendPolicyServiceInterface::class);
        $sendPolicyMock->method('getCooldownRemaining')
            ->with($userId = uniqid())
            ->willReturn(42);

        $this->assertSame(42, $this->getSut(sendPolicy: $sendPolicyMock)->getCooldownRemaining($userId));
    }

    #[Test]
    public function getRemainingAttemptsReturnsZeroWhenAttemptsExceedMax(): void
    {
        $stateStub = $this->createStub(OtpChallengeStateInterface::class);
        $stateStub->method('getAttempts')->willReturn(7);

        $stateServiceStub = $this->createStub(OtpChallengeStateServiceInterface::class);
        $stateServiceStub->method('getChallengeState')->willReturn($stateStub);

        $codeValidatorStub = $this->createStub(OtpCodeValidatorServiceInterface::class);
        $codeValidatorStub->method('getMaxAttempts')->willReturn(5);

        $sut = $this->getSut(stateService: $stateServiceStub, codeValidator: $codeValidatorStub);

        $this->assertSame(0, $sut->getRemainingAttempts(uniqid()));
    }

    private function getSut(
        OtpChallengeStateServiceInterface $stateService = null,
        OtpCodeValidatorServiceInterface $codeValidator = null,
        OtpCodeGeneratorServiceInterface $codeGenerator = null,
        OtpNotifierFactoryInterface $notifierFactory = null,
        OtpSendPolicyServiceInterface $sendPolicy = null,
    ): OtpFacade {
        return new OtpFacade(
            stateService: $stateService ?? $this->createStub(OtpChallengeStateServiceInterface::class),
            codeValidator: $codeValidator ?? $this->createStub(OtpCodeValidatorServiceInterface::class),
            codeGenerator: $codeGenerator ?? $this->createStub(OtpCodeGeneratorServiceInterface::class),
            notifierFactory: $notifierFactory ?? $this->createStub(OtpNotifierFactoryInterface::class),
            sendPolicy: $sendPolicy ?? $this->createStub(OtpSendPolicyServiceInterface::class),
        );
    }
}
