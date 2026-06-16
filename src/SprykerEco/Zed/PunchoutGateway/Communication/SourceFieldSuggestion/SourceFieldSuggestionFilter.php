<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\SourceFieldSuggestion;

use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class SourceFieldSuggestionFilter implements SourceFieldSuggestionFilterInterface
{
    protected const string SEPARATOR = '&';

    public function __construct(protected PunchoutGatewayConfig $config)
    {
    }

    public function filterByTerm(array $suggestions, string $term): array
    {
        $limit = $this->config->getSourceFieldSuggestionLimit();

        if ($term === '') {
            return array_slice($suggestions, 0, $limit);
        }

        $separatorPosition = strrpos($term, static::SEPARATOR);

        $prefix = null;

        if ($separatorPosition !== false) {
            $prefix = substr($term, 0, $separatorPosition + 1);

            $term = substr($term, $separatorPosition + 1);
        }

        $suggestions = array_values(
            array_filter(
                $suggestions,
                static fn (string $suggestion): bool => stripos($suggestion, $term) !== false,
            ),
        );

        $suggestions = array_slice($suggestions, 0, $limit);

        if ($prefix !== null) {
            $suggestions = array_map(
                static fn (string $suggestion): string => $prefix . $suggestion,
                $suggestions,
            );
        }

        return $suggestions;
    }
}
