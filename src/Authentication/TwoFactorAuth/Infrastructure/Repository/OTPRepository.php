<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

class OTPRepository implements OTPRepositoryInterface
{
    private const TABLE = 'oe_security_2fa';

    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
    ) {
    }

    public function find(string $userId): ?OTP
    {
        return $this->findByColumn('OXUSERID', $userId);
    }

    public function findBySid(string $sid): ?OTP
    {
        return $this->findByColumn('SID', $sid);
    }

    public function save(OTP $otp, string $rawCode): void
    {
        $hashedCode = hash('sha256', $rawCode . $otp->getUserId());

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->insert(self::TABLE)
            ->values([
                'OXID' => ':oxid',
                'OXUSERID' => ':userId',
                'OTP_CODE' => ':code',
                'OTP_ATTEMPTS' => ':attempts',
                'OTP_EXPIRES_AT' => ':expiresAt',
                'SID' => ':sid',
                'CONTEXT' => ':context',
                'LAST_SENT_AT' => ':lastSentAt',
            ])
            ->setParameters([
                'oxid' => $this->generateOxid(),
                'userId' => $otp->getUserId(),
                'code' => $hashedCode,
                'attempts' => $otp->getAttempts(),
                'expiresAt' => $otp->getExpiresAt()->format('Y-m-d H:i:s'),
                'sid' => $otp->getSid(),
                'context' => $otp->getContext(),
                'lastSentAt' => $otp->getLastSentAt()?->format('Y-m-d H:i:s'),
            ])
            ->execute();
    }

    public function delete(string $userId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete(self::TABLE)
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
            ->execute();
    }

    public function incrementAttempts(string $userId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->update(self::TABLE)
            ->set('OTP_ATTEMPTS', 'OTP_ATTEMPTS + 1')
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
            ->execute();
    }

    private function findByColumn(string $column, string $value): ?OTP
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $result = $queryBuilder
            ->select(
                'OXUSERID',
                'OTP_CODE',
                'OTP_ATTEMPTS',
                'OTP_EXPIRES_AT',
                'SID',
                'CONTEXT',
                'LAST_SENT_AT'
            )
            ->from(self::TABLE)
            ->where("{$column} = :value")
            ->setParameter('value', $value)
            ->setMaxResults(1)
            ->execute();

        if (is_object($result)) {
            $row = $result->fetchAssociative();
            if ($row) {
                return $this->mapRow($row);
            }
        }

        return null;
    }

    private function mapRow(array $row): OTP
    {
        return new OTP(
            userId: $row['OXUSERID'],
            code: $row['OTP_CODE'] ?? '',
            expiresAt: new \DateTimeImmutable($row['OTP_EXPIRES_AT'] ?? 'now'),
            attempts: (int) $row['OTP_ATTEMPTS'],
            lastSentAt: $row['LAST_SENT_AT']
                ? new \DateTimeImmutable($row['LAST_SENT_AT'])
                : null,
            sid: $row['SID'] ?? '',
            context: $row['CONTEXT'] ?? 'frontend',
        );
    }

    private function generateOxid(): string
    {
        return bin2hex(random_bytes(16));
    }
}
