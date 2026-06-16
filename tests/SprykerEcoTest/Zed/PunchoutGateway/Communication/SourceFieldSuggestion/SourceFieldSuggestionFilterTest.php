<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\SourceFieldSuggestion;

use Codeception\Test\Unit;
use SprykerEco\Zed\PunchoutGateway\Communication\SourceFieldSuggestion\SourceFieldSuggestionFilter;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group SourceFieldSuggestion
 * @group SourceFieldSuggestionFilterTest
 */
class SourceFieldSuggestionFilterTest extends Unit
{
    protected const int LIMIT = 3;

    public function testFilterByTermWithEmptyTermReturnsAllSuggestionsWithinLimit(): void
    {
        $suggestions = ['item.sku', 'item.name'];
        $result = $this->createFilter()->filterByTerm($suggestions, '');

        $this->assertSame($suggestions, $result);
    }

    public function testFilterByTermWithEmptyTermAppliesLimit(): void
    {
        $suggestions = ['item.sku', 'item.name', 'item.price', 'item.quantity', 'quote.grand_total'];

        $result = $this->createFilter()->filterByTerm($suggestions, '');

        $this->assertCount(static::LIMIT, $result);
        $this->assertSame(['item.sku', 'item.name', 'item.price'], $result);
    }

    public function testFilterByTermFiltersMatchingSubstrings(): void
    {
        $suggestions = ['item.sku', 'item.name', 'quote.grand_total'];

        $result = $this->createFilter()->filterByTerm($suggestions, 'item');

        $this->assertSame(['item.sku', 'item.name'], $result);
    }

    public function testFilterByTermIsCaseInsensitive(): void
    {
        $suggestions = ['item.sku', 'item.NAME', 'quote.total'];

        $result = $this->createFilter()->filterByTerm($suggestions, 'NAME');

        $this->assertSame(['item.NAME'], $result);
    }

    public function testFilterByTermWithNoMatchReturnsEmptyArray(): void
    {
        $suggestions = ['item.sku', 'item.name'];

        $result = $this->createFilter()->filterByTerm($suggestions, 'nonexistent');

        $this->assertSame([], $result);
    }

    public function testFilterByTermAppliesLimitToFilteredResults(): void
    {
        $suggestions = ['item.sku', 'item.name', 'item.price', 'item.quantity'];

        $result = $this->createFilter()->filterByTerm($suggestions, 'item');

        $this->assertCount(static::LIMIT, $result);
        $this->assertSame(['item.sku', 'item.name', 'item.price'], $result);
    }

    public function testFilterByTermWithSeparatorMatchesTrailingSegmentAndPrependsPrefix(): void
    {
        $suggestions = ['item.sku', 'item.name', 'quote.total'];

        $result = $this->createFilter()->filterByTerm($suggestions, 'item.sku&item');

        $this->assertSame(['item.sku&item.sku', 'item.sku&item.name'], $result);
    }

    public function testFilterByTermWithSeparatorAndNoTrailingMatchReturnsEmptyArray(): void
    {
        $suggestions = ['item.sku', 'item.name'];

        $result = $this->createFilter()->filterByTerm($suggestions, 'item.sku&nonexistent');

        $this->assertSame([], $result);
    }

    protected function createFilter(): SourceFieldSuggestionFilter
    {
        $configMock = $this->createMock(PunchoutGatewayConfig::class);
        $configMock->method('getSourceFieldSuggestionLimit')->willReturn(static::LIMIT);

        return new SourceFieldSuggestionFilter($configMock);
    }
}
