<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP;

use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

class OTPGeneratorService implements OTPGeneratorServiceInterface
{
    public function generate(string $userId, int $length): OTP
    {
        $code = $this->generateCode($length);

        return new OTP(
            userId: $userId,
            code: $code,
            expiresAt: new \DateTimeImmutable(),
            attempts: 0,
            lastSentAt: null,
            sid: '',
            context: 'frontend',
        );
    }

    private function generateCode(int $length): string
    {
        $max = (int) str_repeat('9', $length);
        $number = random_int(0, $max);

        return str_pad((string) $number, $length, '0', STR_PAD_LEFT);
    }
}
