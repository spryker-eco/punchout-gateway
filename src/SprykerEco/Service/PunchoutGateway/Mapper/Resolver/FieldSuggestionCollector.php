<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Mapper\Resolver;

class FieldSuggestionCollector
{
    /**
     * @param array<string, \SprykerEco\Service\PunchoutGateway\Dependency\Plugin\PunchoutFieldMapperPluginInterface> $fieldMapperPlugins
     */
    public function __construct(protected array $fieldMapperPlugins)
    {
    }

    /**
     * @return array<string>
     */
    public function collect(): array
    {
        $suggestions = [];

        foreach ($this->fieldMapperPlugins as $fieldMapperPlugin) {
            $suggestions[] = $fieldMapperPlugin->getPossibleValues();
        }

        $suggestions = array_merge(...$suggestions);

        sort($suggestions);

        return array_values(array_unique($suggestions));
    }
}
