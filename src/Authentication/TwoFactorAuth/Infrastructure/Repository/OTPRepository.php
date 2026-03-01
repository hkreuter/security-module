<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\DTO\OTP;

class OTPRepository implements OTPRepositoryInterface
{
    private const TABLE_NAME = 'oe_security_2fa';

    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
    ) {
    }

    public function find(string $userId): ?OTP
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $result = $queryBuilder
            ->select([
                'OXID',
                'OXUSERID',
                'OTP_CODE',
                'OTP_ATTEMPTS',
                'OTP_EXPIRES_AT',
                'SID',
                'CONTEXT',
                'LAST_SENT_AT',
            ])
            ->from(self::TABLE_NAME)
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
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

    public function findBySid(string $sid): ?OTP
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $result = $queryBuilder
            ->select([
                'OXID',
                'OXUSERID',
                'OTP_CODE',
                'OTP_ATTEMPTS',
                'OTP_EXPIRES_AT',
                'SID',
                'CONTEXT',
                'LAST_SENT_AT',
            ])
            ->from(self::TABLE_NAME)
            ->where('SID = :sid')
            ->setParameter('sid', $sid)
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

    public function save(OTP $otp, string $rawCode): void
    {
        $hashedCode = hash('sha256', $rawCode . $otp->userId);
        $oxid = $this->generateOxid();

        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder
            ->insert(self::TABLE_NAME)
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
                'oxid' => $oxid,
                'userId' => $otp->userId,
                'code' => $hashedCode,
                'attempts' => $otp->attempts,
                'expiresAt' => $otp->expiresAt->format('Y-m-d H:i:s'),
                'sid' => $otp->sid,
                'context' => $otp->context,
                'lastSentAt' => $otp->lastSentAt?->format('Y-m-d H:i:s'),
            ])
            ->execute();
    }

    public function delete(string $userId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
            ->execute();
    }

    public function incrementAttempts(string $userId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder
            ->update(self::TABLE_NAME)
            ->set('OTP_ATTEMPTS', 'OTP_ATTEMPTS + 1')
            ->where('OXUSERID = :userId')
            ->setParameter('userId', $userId)
            ->execute();
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
        return md5(uniqid((string) mt_rand(), true));
    }
}
