<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration;

use OxidEsales\EshopCommunity\Internal\Container\ContainerBuilderFactory;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ServiceAvailabilityTest extends IntegrationTestCase
{
    private static ContainerInterface $cachedContainer;

    public static function setUpBeforeClass(): void
    {
        $containerBuilder = (new ContainerBuilderFactory())->create();
        $container = $containerBuilder->getContainer();
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        $container->compile(true);

        self::$cachedContainer = $container;
    }

    #[DataProvider('serviceAvailabilityDataProvider')]
    public function testServicesAvailable(string $serviceId): void
    {
        self::assertIsObject(self::$cachedContainer->get($serviceId));
    }

    public static function serviceAvailabilityDataProvider(): array
    {
        // phpcs:disable
        return [
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Factory\TwoFAServiceFactoryInterface::class],

            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAServiceInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\TwoFAUserServiceInterface::class],

            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Settings\TwoFAShopSettingsInterface::class],

            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\AuthCodeRequestInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Transput\JsonResponseInterface::class],

            [\OxidEsales\SecurityModule\Shared\Infrastructure\Factory\EmailFactoryInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Factory\UserFactoryInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Factory\UserModelFactoryInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\UserRepositoryInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Service\UserLoginAdapterInterface::class],

            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Infrastructure\Repository\OtpChallengeStateRepositoryInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpChallengeStateServiceInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeValidatorServiceInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeGeneratorServiceInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpCodeHasherServiceInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Service\OtpSendPolicyServiceInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\OtpNotifierInterface::class],
            [\OxidEsales\SecurityModule\Authentication\TwoFactorAuth\OTP\Notifier\Factory\OtpNotifierFactoryInterface::class],

            [\OxidEsales\SecurityModule\PasswordReuse\Service\PasswordHistoryServiceInterface::class],
        ];
        // phpcs:enable
    }
}
