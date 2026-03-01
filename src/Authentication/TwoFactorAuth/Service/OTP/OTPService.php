<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;

class OTPService implements OTPServiceInterface
{
    public function __construct(
        private readonly OTPRepositoryInterface $repository,
        private readonly OTPGeneratorServiceInterface $generator,
        private readonly ExpirationServiceInterface $expirationService,
        private readonly ModuleSettingsServiceInterface $settings,
    ) {
    }

    public function getOTP(string $userId): OTP
    {
        $existing = $this->repository->find($userId);

        if ($existing !== null && $existing->expiresAt > new \DateTimeImmutable()) {
            return $existing;
        }

        if ($existing !== null) {
            $this->repository->delete($userId);
        }

        return $this->createNew($userId);
    }

    public function delete(string $userId): void
    {
        $this->repository->delete($userId);
    }

    private function createNew(string $userId): OTP
    {
        $length = $this->settings->getOtpLength();
        $generated = $this->generator->generate($userId, $length);
        $rawCode = $generated->code;

        $expiresAt = $this->expirationService->calculate();

        $otp = new OTP(
            userId: $userId,
            code: $rawCode,
            expiresAt: $expiresAt,
            attempts: 0,
            lastSentAt: null,
            sid: $generated->sid,
            context: $generated->context,
        );

        $this->repository->save($otp, $rawCode);

        return $otp;
    }
}
