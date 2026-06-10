<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\SecurityModule\GraphQL\Shared;

/**
 * Cross-domain mapper that registers this module's oxapi controllers and types with graphql-base's
 * schema. Maps per domain, per type (mirroring graphql-storefront-administration); add a couple of
 * lines per new domain.
 *
 * Plain, duck-typed: deliberately does NOT implement graphql-base's NamespaceMapperInterface, so it
 * has zero structural dependency on graphql-base. Tagged `graphql_namespace_mapper`; that tag is
 * consumed only by graphql-base's compiler pass, so when graphql-base is absent the service is
 * simply inert (and autowiring loads nothing).
 */
final class NamespaceMapper
{
    private const SPACE = '\\OxidEsales\\SecurityModule\\GraphQL\\';

    public function getControllerNamespaceMapping(): array
    {
        return [
            self::SPACE . 'Authentication\\Controller' => __DIR__ . '/../Authentication/Controller/',
        ];
    }

    public function getTypeNamespaceMapping(): array
    {
        return [
            self::SPACE . 'Authentication\\DataType' => __DIR__ . '/../Authentication/DataType/',
        ];
    }
}
