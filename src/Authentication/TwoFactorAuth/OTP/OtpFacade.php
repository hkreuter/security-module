<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP;

use DateTimeImmutable;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\AttemptLimitExceededException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Exception\ResendCooldownException;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\Factory\OtpNotifierFactoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpChallengeStateServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeGeneratorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeValidatorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpSendPolicyServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAResendableInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface;

class OtpFacade implements TwoFAServiceInterface, TwoFAResendableInterface
{
    public function __construct(
        private OtpChallengeStateServiceInterface $stateService,
        private OtpCodeValidatorServiceInterface $codeValidator,
        private OtpCodeGeneratorServiceInterface $codeGenerator,
        private OtpNotifierFactoryInterface $notifierFactory,
        private OtpSendPolicyServiceInterface $sendPolicy,
    ) {
    }

    public function isVerified(string $userId): bool
    {
        $state = $this->stateService->getChallengeState($userId);

        if ($state === null || $state->getVerifiedAt() === null) {
            return false;
        }

        return $state->getExpiresAt() > new DateTimeImmutable();
    }

    public function triggerChallenge(string $userId): void
    {
        if (!$this->sendPolicy->canSend($userId)) {
            return;
        }

        $code = $this->codeGenerator->generateCode();
        $this->stateService->createChallengeState($userId, $code);
        $this->notifierFactory->create($userId)->notify($userId, $code);
    }

    public function invalidateChallenge(string $userId): void
    {
        $this->stateService->deleteChallengeState($userId);
    }

    public function verify(string $userId, #[\SensitiveParameter] string $code): void
    {
        $this->codeValidator->validateCode($userId, $code);
        $this->stateService->markVerified($userId);
    }

    public function resend(string $userId): void
    {
        if (!$this->sendPolicy->canSend($userId)) {
            throw new ResendCooldownException();
        }

        if ($this->getRemainingAttempts($userId) === 0) {
            throw new AttemptLimitExceededException();
        }

        $code = $this->codeGenerator->generateCode();
        $this->stateService->refreshChallengeState($userId, $code);
        $this->notifierFactory->create($userId)->notify($userId, $code);
    }

    public function getRemainingAttempts(string $userId): int
    {
        $state = $this->stateService->getChallengeState($userId);
        $attempts = $state?->getAttempts() ?? 0;

        return max(0, $this->codeValidator->getMaxAttempts() - $attempts);
    }

    public function getCooldownRemaining(string $userId): int
    {
        return $this->sendPolicy->getCooldownRemaining($userId);
    }
}
