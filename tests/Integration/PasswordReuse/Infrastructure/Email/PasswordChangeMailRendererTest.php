<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Infrastructure\Email;

use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailRenderer;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailRendererInterface;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PasswordChangeMailRendererTest extends IntegrationTestCase
{
    #[Test]
    #[DataProvider('templateProvider')]
    public function rendersTemplateWithData(string $template): void
    {
        $ident = 'pwdchange_' . substr(uniqid('', true), 0, 20);
        $value = (string) random_int(100000, 999999);
        $this->seedContent($ident, 'Marker: {{ otp }}');

        $output = $this->getSut()->render(
            $template,
            ['otp' => $value, 'contentIdent' => $ident],
        );

        $this->assertStringContainsString($value, $output);
    }

    public static function templateProvider(): array
    {
        $namespace = '@' . Module::MODULE_ID;

        return [
            'plain' => ["$namespace/email/plain/twofactorotp.html.twig"],
            'html' => ["$namespace/email/html/twofactorotp.html.twig"],
        ];
    }

    private function seedContent(string $ident, string $body): void
    {
        $content = oxNew(Content::class);
        $content->assign([
            'oxloadid' => $ident,
            'oxactive' => 1,
            'oxshopid' => 1,
            'oxsnippet' => 1,
            'oxtype' => 0,
            'oxtitle' => 'Password change',
            'oxcontent' => $body,
        ]);
        $content->save();
    }

    private function getSut(
        ?TemplateRendererBridgeInterface $rendererBridge = null,
    ): PasswordChangeMailRendererInterface {
        return new PasswordChangeMailRenderer(
            rendererBridge: $rendererBridge
                ?? ContainerFactory::getInstance()->getContainer()->get(TemplateRendererBridgeInterface::class),
        );
    }
}
