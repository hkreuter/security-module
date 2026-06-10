<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\Tests\Unit\GraphQL\Shared;

use OxidEsales\SecurityModule\GraphQL\Shared\NamespaceMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NamespaceMapperTest extends TestCase
{
    #[Test]
    public function controllerMappingPointsAuthenticationControllerNamespaceAtAnExistingDirectory(): void
    {
        $mapping = $this->getSut()->getControllerNamespaceMapping();

        $namespace = '\\OxidEsales\\SecurityModule\\GraphQL\\Authentication\\Controller';
        $this->assertArrayHasKey($namespace, $mapping);
        $this->assertDirectoryExists($mapping[$namespace]);
    }

    #[Test]
    public function typeMappingPointsAuthenticationDataTypeNamespaceAtAnExistingDirectory(): void
    {
        $mapping = $this->getSut()->getTypeNamespaceMapping();

        $namespace = '\\OxidEsales\\SecurityModule\\GraphQL\\Authentication\\DataType';
        $this->assertArrayHasKey($namespace, $mapping);
        $this->assertDirectoryExists($mapping[$namespace]);
    }

    private function getSut(): NamespaceMapper
    {
        return new NamespaceMapper();
    }
}
