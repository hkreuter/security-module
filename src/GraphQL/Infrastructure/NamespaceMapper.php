<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Infrastructure;

/**
 * SPIKE EXPERIMENT — plain, duck-typed namespace mapper. Deliberately does NOT implement
 * graphql-base's NamespaceMapperInterface, so it has zero structural dependency on graphql-base.
 * Tagged `graphql_namespace_mapper`; that tag is consumed only by graphql-base's compiler pass,
 * so when graphql-base is absent the service is simply inert (and autowiring loads nothing).
 */
final class NamespaceMapper
{
    private const SPACE = '\\OxidEsales\\SecurityModule\\GraphQL\\';

    public function getControllerNamespaceMapping(): array
    {
        return [self::SPACE . 'Controller' => __DIR__ . '/../Controller/'];
    }

    public function getTypeNamespaceMapping(): array
    {
        return [self::SPACE . 'DataType' => __DIR__ . '/../DataType/'];
    }
}
