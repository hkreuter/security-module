<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Integration\PasswordReuse\Infrastructure\Email;

use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;
use OxidEsales\SecurityModule\Core\Module;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailContent;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailRenderer;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email\PasswordChangeMailRendererInterface;
use OxidEsales\SecurityModule\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class PasswordChangeMailContentSeedTest extends IntegrationTestCase
{
    #[Test]
    public function seedMigrationCreatesEditableContentForBothLanguages(): void
    {
        $row = $this->getConnection()->fetchAssociative(
            'SELECT OXACTIVE, OXTITLE, OXCONTENT, OXTITLE_1, OXCONTENT_1 FROM oxcontents WHERE OXLOADID = ?',
            [PasswordChangeMailContent::IDENT],
        );

        $this->assertIsArray($row, 'Expected a seeded oxcontents row for the password-change mail ident.');
        $this->assertSame(1, (int) $row['OXACTIVE']);
        $this->assertNotSame('', trim((string) $row['OXTITLE']), 'Expected a German subject.');
        $this->assertNotSame('', trim((string) $row['OXCONTENT']), 'Expected a German body.');
        $this->assertNotSame('', trim((string) $row['OXTITLE_1']), 'Expected an English subject.');
        $this->assertNotSame('', trim((string) $row['OXCONTENT_1']), 'Expected an English body.');
    }

    #[Test]
    public function seededBodyStatesTheChangeTimeAndContactSupport(): void
    {
        $ident = PasswordChangeMailContent::IDENT;
        $marker = '2026-08-21 10:15';

        $data = ['changedAt' => $marker, 'contentIdent' => $ident];
        $html = $this->getSut()->render(self::htmlTemplate(), $data);
        $plain = $this->getSut()->render(self::plainTemplate(), $data);

        $this->assertStringContainsString($marker, $html);
        $this->assertStringContainsString($marker, $plain);
    }

    #[Test]
    #[DataProvider('templateProvider')]
    public function rendersTemplateWithData(string $template): void
    {
        $ident = 'pwdchange_' . substr(uniqid('', true), 0, 20);
        $marker = '2026-01-02 03:04';
        $this->seedContent($ident, 'Changed at: {{ changedAt }}');

        $output = $this->getSut()->render(
            $template,
            ['changedAt' => $marker, 'contentIdent' => $ident],
        );

        $this->assertStringContainsString($marker, $output);
    }

    public static function templateProvider(): array
    {
        return [
            'plain' => [self::plainTemplate()],
            'html' => [self::htmlTemplate()],
        ];
    }

    private static function htmlTemplate(): string
    {
        return '@' . Module::MODULE_ID . '/email/html/passwordchange.html.twig';
    }

    private static function plainTemplate(): string
    {
        return '@' . Module::MODULE_ID . '/email/plain/passwordchange.html.twig';
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

    private function getSut(): PasswordChangeMailRendererInterface
    {
        return new PasswordChangeMailRenderer(
            rendererBridge: ContainerFactory::getInstance()
                ->getContainer()
                ->get(TemplateRendererBridgeInterface::class),
        );
    }

    private function getConnection(): Connection
    {
        return $this->get(QueryBuilderFactoryInterface::class)
            ->create()
            ->getConnection();
    }
}
