<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Authentication\TwoFactorAuth;

use OxidEsales\EshopCommunity\Tests\TestContainerFactory;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapter;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapterInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailFactory;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailFactoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepository;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository\OTPRepositoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ExpirationService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ExpirationServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPAttemptServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTP\OTPServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPGeneratorService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPGeneratorServiceInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPValidatorService;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\OTPValidatorServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class DIWiringTest extends TestCase
{
    private static ?ContainerBuilder $container = null;

    public static function setUpBeforeClass(): void
    {
        $container = (new TestContainerFactory())->create();

        $definition = new Definition();
        $definition->setSynthetic(true);
        $definition->setPublic(true);
        $container->setDefinition(
            ModuleSettingsServiceInterface::class,
            $definition,
        );

        $container->compile(true);
        $container->get(
            'oxid_esales.module.install.service.launched_shop_project_configuration_generator',
        )->generate();

        self::$container = $container;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $stub = $this->createMock(ModuleSettingsServiceInterface::class);
        $stub->method('isTwoFactorAuthEnabled')->willReturn(true);
        $stub->method('getOtpLength')->willReturn(6);
        $stub->method('getOtpLifetime')->willReturn(300);
        $stub->method('getMaxAttempts')->willReturn(5);
        $stub->method('getCooldown')->willReturn(60);

        self::$container->set(ModuleSettingsServiceInterface::class, $stub);
    }

    #[DataProvider('serviceProvider')]
    public function testServiceResolvesFromContainer(
        string $interfaceFqcn,
        string $expectedClass,
    ): void {
        $service = self::$container->get($interfaceFqcn);

        $this->assertInstanceOf($expectedClass, $service);
    }

    public static function serviceProvider(): \Generator
    {
        yield 'OTPRepository' => [
            OTPRepositoryInterface::class,
            OTPRepository::class,
        ];
        yield 'OTPGeneratorService' => [
            OTPGeneratorServiceInterface::class,
            OTPGeneratorService::class,
        ];
        yield 'OTPValidatorService' => [
            OTPValidatorServiceInterface::class,
            OTPValidatorService::class,
        ];
        yield 'ExpirationService' => [
            ExpirationServiceInterface::class,
            ExpirationService::class,
        ];
        yield 'OTPAttemptService' => [
            OTPAttemptServiceInterface::class,
            OTPAttemptService::class,
        ];
        yield 'OTPService' => [
            OTPServiceInterface::class,
            OTPService::class,
        ];
        yield 'EmailFactory' => [
            EmailFactoryInterface::class,
            EmailFactory::class,
        ];
        yield 'EmailAdapter' => [
            EmailAdapterInterface::class,
            EmailAdapter::class,
        ];
    }
}
