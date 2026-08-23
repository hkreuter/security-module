<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Email;

use OxidEsales\EshopCommunity\Internal\Framework\Templating\TemplateRendererBridgeInterface;

class PasswordChangeMailRenderer implements PasswordChangeMailRendererInterface
{
    public function __construct(
        private TemplateRendererBridgeInterface $rendererBridge,
    ) {
    }

    public function render(string $template, array $data): string
    {
        return $this->rendererBridge
            ->getTemplateRenderer()
            ->renderTemplate($template, $data);
    }
}
