<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\SourceFieldSuggestion;

interface SourceFieldSuggestionFilterInterface
{
    /**
     * @param array<string> $suggestions
     *
     * @return array<string>
     */
    public function filterByTerm(array $suggestions, string $term): array;
}
