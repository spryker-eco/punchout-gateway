<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Mapper\Resolver;

use Generated\Shared\Transfer\MappingSourceTransfer;

interface FieldValueResolverInterface
{
    /**
     * @api
     *
     * Resolves a source expression to a value using the registered plugin registry.
     *
     * Supported expression formats:
     * - `pluginKey.field.path` — dynamic value from the registered plugin (e.g. `item.sku`)
     * - `"literal"` or `'literal'` — constant string value (e.g. `"EA"`)
     * - Segments joined by `&` — concatenated result (e.g. `item.sku&"_suffix"`)
     *   Note: `&` inside quoted strings is not supported.
     *
     * Null segments in a concatenation are treated as empty string.
     * Returns null for null/empty expressions or when a single segment cannot be resolved.
     */
    public function resolve(?string $expression, MappingSourceTransfer $mappingSourceTransfer): mixed;
}
