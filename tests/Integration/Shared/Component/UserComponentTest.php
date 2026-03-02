<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\Shared\Component;

use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Controller\StartController;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\SecurityModule\Captcha\Service\ModuleSettingsServiceInterface as CaptchaSettingsInterface;
use OxidEsales\SecurityModule\Shared\Component\UserComponent as SecurityUserComponent;

final class UserComponentTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->disableCaptcha();
    }

    public function tearDown(): void
    {
        $this->restoreCaptcha();
        parent::tearDown();
    }

    public function testExtensionIsRegistered(): void
    {
        $component = oxNew(UserComponent::class);

        $this->assertInstanceOf(SecurityUserComponent::class, $component);
    }

    public function testLoginDelegatesToParentOnInvalidCredentials(): void
    {
        $this->mockRequest('nonexistent@example.com', 'wrongpassword');

        $component = $this->createComponent();
        $result = $component->login();

        $this->assertSame('user', $result);
    }

    private function createComponent(): SecurityUserComponent
    {
        $parentController = oxNew(StartController::class);
        $component = oxNew(UserComponent::class);
        $component->setParent($parentController);

        return $component;
    }

    private function mockRequest(string $username, string $password): void
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequestEscapedParameter', 'getRequestParameter'])
            ->getMock();

        $request->method('getRequestEscapedParameter')
            ->willReturnMap([
                ['lgn_usr', null, $username],
                ['lgn_cook', null, ''],
            ]);

        $request->method('getRequestParameter')
            ->willReturnMap([
                ['lgn_pwd', null, $password],
            ]);

        Registry::set(Request::class, $request);
    }

    private function disableCaptcha(): void
    {
        $captchaSettings = ContainerFacade::get(CaptchaSettingsInterface::class);
        $captchaSettings->saveIsCaptchaEnabled(false);
    }

    private function restoreCaptcha(): void
    {
        $captchaSettings = ContainerFacade::get(CaptchaSettingsInterface::class);
        $captchaSettings->saveIsCaptchaEnabled(true);
    }
}
