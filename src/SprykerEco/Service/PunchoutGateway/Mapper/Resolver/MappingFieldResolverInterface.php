<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway\Mapper\Resolver;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MappingSourceTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface MappingFieldResolverInterface
{
    /**
     * Resolves a required field from $fieldMap: falls back to $fallback() when the map
     * has no entry, null, or an empty string, or when the expression resolves to null.
     *
     * @param array<string, string|null> $fieldMap
     */
    public function resolveWithFallback(array $fieldMap, string $key, MappingSourceTransfer $mappingSourceTransfer, callable $fallback): mixed;

    /**
     * Resolves an optional field from $fieldMap: returns null when the map has no entry,
     * null, or an empty string, or when the expression resolves to null.
     *
     * @param array<string, string|null> $fieldMap
     */
    public function resolveOrSkip(array $fieldMap, string $key, MappingSourceTransfer $mappingSourceTransfer): mixed;

    /**
     * Resolves a raw field-mapping expression against the given source transfer.
     */
    public function resolve(?string $expression, MappingSourceTransfer $mappingSourceTransfer): mixed;

    /**
     * Builds a MappingSourceTransfer populated with the quote and, when provided, a single item.
     */
    public function buildMappingSource(QuoteTransfer $quoteTransfer, ?ItemTransfer $itemTransfer = null): MappingSourceTransfer;
}
