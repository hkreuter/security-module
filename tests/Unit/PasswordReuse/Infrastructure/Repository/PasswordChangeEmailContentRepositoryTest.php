<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\PasswordReuse\Infrastructure\Repository;

use OxidEsales\Eshop\Application\Model\Content;
use OxidEsales\SecurityModule\PasswordReuse\Infrastructure\Repository\PasswordChangeEmailContentRepository;
use OxidEsales\SecurityModule\Shared\Infrastructure\Factory\ContentModelFactoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PasswordChangeEmailContentRepositoryTest extends TestCase
{
    #[Test]
    public function getEmailSubjectReturnsTitleForActiveContent(): void
    {
        $title = uniqid();
        $contentStub = $this->contentStub(loaded: true, active: true, title: $title);

        $sut = $this->getSut($this->factoryReturning($contentStub));

        $this->assertSame($title, $sut->getEmailSubject(uniqid()));
    }

    #[Test]
    public function getEmailSubjectReturnsNullWhenContentDoesNotLoad(): void
    {
        $contentStub = $this->contentStub(loaded: false, active: false, title: uniqid());

        $sut = $this->getSut($this->factoryReturning($contentStub));

        $this->assertNull($sut->getEmailSubject(uniqid()));
    }

    #[Test]
    public function getEmailSubjectReturnsNullWhenContentIsInactive(): void
    {
        $contentStub = $this->contentStub(loaded: true, active: false, title: uniqid());

        $sut = $this->getSut($this->factoryReturning($contentStub));

        $this->assertNull($sut->getEmailSubject(uniqid()));
    }

    #[Test]
    public function getEmailSubjectReturnsNullWhenTitleIsEmpty(): void
    {
        $contentStub = $this->contentStub(loaded: true, active: true, title: '   ');

        $sut = $this->getSut($this->factoryReturning($contentStub));

        $this->assertNull($sut->getEmailSubject(uniqid()));
    }

    private function contentStub(bool $loaded, bool $active, string $title): Content
    {
        $contentStub = $this->createStub(Content::class);
        $contentStub->method('loadByIdent')->willReturn($loaded);
        $contentStub->method('isActive')->willReturn($active);
        $contentStub->method('getTitle')->willReturn($title);

        return $contentStub;
    }

    private function factoryReturning(Content $content): ContentModelFactoryInterface
    {
        $factoryStub = $this->createStub(ContentModelFactoryInterface::class);
        $factoryStub->method('create')->willReturn($content);

        return $factoryStub;
    }

    private function getSut(?ContentModelFactoryInterface $contentFactory = null): PasswordChangeEmailContentRepository
    {
        return new PasswordChangeEmailContentRepository(
            contentFactory: $contentFactory ?? $this->createStub(ContentModelFactoryInterface::class),
        );
    }
}
