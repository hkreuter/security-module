<?php

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\Authentication\TwoFactorAuth\Infrastructure\Provider\Email;

use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailAdapter;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Provider\Email\EmailFactoryInterface;
use OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Service\ModuleSettingsServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailAdapter::class)]
final class EmailAdapterTest extends TestCase
{
    private const RECIPIENT = 'user@example.com';
    private const CODE = '123456';

    private MockObject&Email $email;
    private MockObject&TemplateRendererInterface $renderer;
    private MockObject&ModuleSettingsServiceInterface $settings;

    protected function setUp(): void
    {
        $this->email = $this->createMock(Email::class);
        $this->email->method('getShop')->willReturn($this->createShopStub());
        $this->email->method('getViewData')->willReturn([]);
        $this->email->method('send')->willReturn(true);

        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->renderer->method('renderTemplate')->willReturn('');

        $this->settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $this->settings->method('getOtpLifetime')->willReturn(300);
    }

    public function testSendSetsOtpCodeOnEmail(): void
    {
        $this->email->expects($this->atLeastOnce())
            ->method('setViewData')
            ->willReturnCallback(function (string $key, mixed $value): void {
                if ($key === 'otpCode') {
                    $this->assertSame(self::CODE, $value);
                }
            });

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);
    }

    public function testSendSetsLifetimeMinutesOnEmail(): void
    {
        $capturedValues = [];
        $this->email->expects($this->atLeastOnce())
            ->method('setViewData')
            ->willReturnCallback(function (string $key, mixed $value) use (&$capturedValues): void {
                $capturedValues[$key] = $value;
            });

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);

        $this->assertSame(5, $capturedValues['lifetimeMinutes']);
    }

    #[DataProvider('lifetimeProvider')]
    public function testLifetimeIsConvertedToMinutesRoundedUp(int $seconds, int $expectedMinutes): void
    {
        $this->settings = $this->createMock(ModuleSettingsServiceInterface::class);
        $this->settings->method('getOtpLifetime')->willReturn($seconds);

        $capturedValues = [];
        $this->email->expects($this->atLeastOnce())
            ->method('setViewData')
            ->willReturnCallback(function (string $key, mixed $value) use (&$capturedValues): void {
                $capturedValues[$key] = $value;
            });

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);

        $this->assertSame($expectedMinutes, $capturedValues['lifetimeMinutes']);
    }

    public static function lifetimeProvider(): \Generator
    {
        yield '300 seconds = 5 minutes' => [300, 5];
        yield '90 seconds rounds up to 2 minutes' => [90, 2];
        yield '60 seconds = 1 minute' => [60, 1];
        yield '61 seconds rounds up to 2 minutes' => [61, 2];
    }

    public function testSendRendersHtmlAndPlainTemplates(): void
    {
        $renderedTemplates = [];
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->renderer->expects($this->exactly(2))
            ->method('renderTemplate')
            ->willReturnCallback(function (string $template) use (&$renderedTemplates): string {
                $renderedTemplates[] = $template;
                return '';
            });

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);

        $this->assertContains(EmailAdapter::HTML_TEMPLATE, $renderedTemplates);
        $this->assertContains(EmailAdapter::PLAIN_TEMPLATE, $renderedTemplates);
    }

    public function testSendPassesRenderedBodiesToEmail(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->renderer->method('renderTemplate')
            ->willReturnCallback(fn(string $template): string => "body-of-$template");

        $this->email->expects($this->once())
            ->method('setBody')
            ->with('body-of-' . EmailAdapter::HTML_TEMPLATE);

        $this->email->expects($this->once())
            ->method('setAltBody')
            ->with('body-of-' . EmailAdapter::PLAIN_TEMPLATE);

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);
    }

    public function testSendSetsRecipient(): void
    {
        $this->email->expects($this->once())
            ->method('setRecipient')
            ->with(self::RECIPIENT);

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);
    }

    public function testSendCallsSendOnEmail(): void
    {
        $this->email->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);
    }

    public function testSendThrowsWhenEmailSendFails(): void
    {
        $this->email = $this->createMock(Email::class);
        $this->email->method('getShop')->willReturn($this->createShopStub());
        $this->email->method('getViewData')->willReturn([]);
        $this->email->method('send')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->createAdapter()->send(self::RECIPIENT, self::CODE);
    }

    private function createAdapter(): EmailAdapter
    {
        $factory = $this->createMock(EmailFactoryInterface::class);
        $factory->method('create')->willReturn($this->email);

        $bridge = $this->createMock(TemplateRendererBridgeInterface::class);
        $bridge->method('getTemplateRenderer')->willReturn($this->renderer);

        return new EmailAdapter(
            $factory,
            $this->settings,
            $bridge,
        );
    }

    private function createShopStub(): Shop
    {
        $shop = $this->createMock(Shop::class);
        $shop->method('getFieldData')->willReturnMap([
            ['oxinfoemail', 'info@shop.com'],
            ['oxname', 'Test Shop'],
            ['oxorderemail', 'orders@shop.com'],
        ]);

        return $shop;
    }
}
