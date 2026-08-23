<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Authentication\TwoFactorAuth\Infrastructure\Repository;

use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\ContentModelFactoryInterface;

class OtpEmailContentRepository implements OtpEmailContentRepositoryInterface
{
    public function __construct(
        private ContentModelFactoryInterface $contentFactory,
    ) {
    }

    public function getEmailSubject(string $ident): ?string
    {
        $content = $this->contentFactory->create();

        if (!$content->loadByIdent($ident)) {
            return null;
        }

        if (!$content->isActive()) {
            return null;
        }

        $title = trim($content->getTitle());

        return $title !== '' ? $title : null;
    }
}
