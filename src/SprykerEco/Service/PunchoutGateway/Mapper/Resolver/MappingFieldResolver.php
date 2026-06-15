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

class MappingFieldResolver implements MappingFieldResolverInterface
{
    public function __construct(
        protected FieldValueResolverInterface $fieldValueResolver,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, string|null> $fieldMap
     */
    public function resolveWithFallback(array $fieldMap, string $key, MappingSourceTransfer $mappingSourceTransfer, callable $fallback): mixed
    {
        if (!array_key_exists($key, $fieldMap) || $fieldMap[$key] === null || $fieldMap[$key] === '') {
            return $fallback();
        }

        $resolved = $this->fieldValueResolver->resolve($fieldMap[$key], $mappingSourceTransfer);

        return $resolved ?? $fallback();
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, string|null> $fieldMap
     */
    public function resolveOrSkip(array $fieldMap, string $key, MappingSourceTransfer $mappingSourceTransfer): mixed
    {
        if (!array_key_exists($key, $fieldMap) || $fieldMap[$key] === null || $fieldMap[$key] === '') {
            return null;
        }

        return $this->fieldValueResolver->resolve($fieldMap[$key], $mappingSourceTransfer);
    }

    public function resolve(?string $expression, MappingSourceTransfer $mappingSourceTransfer): mixed
    {
        return $this->fieldValueResolver->resolve($expression, $mappingSourceTransfer);
    }

    public function buildMappingSource(QuoteTransfer $quoteTransfer, ?ItemTransfer $itemTransfer = null): MappingSourceTransfer
    {
        $mappingSourceTransfer = new MappingSourceTransfer();
        $mappingSourceTransfer->setQuote($quoteTransfer);

        if ($itemTransfer !== null) {
            $mappingSourceTransfer->setItem($itemTransfer);
        }

        return $mappingSourceTransfer;
    }
}
