<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Mapper\Resolver;

use Generated\Shared\Transfer\MappingSourceTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class FieldValueResolver implements FieldValueResolverInterface
{
    /**
     * @param array<string, \SprykerEco\Service\PunchoutGateway\Dependency\Plugin\PunchoutFieldMapperPluginInterface> $fieldMapperPlugins
     */
    public function __construct(
        protected array $fieldMapperPlugins,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function resolve(?string $expression, MappingSourceTransfer $mappingSourceTransfer): mixed
    {
        if ($expression === null || $expression === '') {
            return null;
        }

        if (str_contains($expression, '&')) {
            return implode('', array_map(
                fn (string $segment) => (string)$this->resolveSingleSegment(trim($segment), $mappingSourceTransfer),
                explode('&', $expression),
            ));
        }

        return $this->resolveSingleSegment($expression, $mappingSourceTransfer);
    }

    protected function resolveSingleSegment(string $expression, MappingSourceTransfer $mappingSourceTransfer): mixed
    {
        if ($expression === '') {
            return null;
        }

        $firstChar = $expression[0];

        if (($firstChar === '"' || $firstChar === "'") && str_ends_with($expression, $firstChar)) {
            return substr($expression, 1, -1);
        }

        $dotPosition = strpos($expression, '.');

        if ($dotPosition === false) {
            $this->punchoutLogger->logGenericErrorMessage(
                sprintf(PunchoutGatewayConfig::ERROR_FIELD_MAPPING_MISSING_DOT_SEPARATOR, $expression),
            );

            return null;
        }

        $pluginKey = substr($expression, 0, $dotPosition);
        $fieldName = substr($expression, $dotPosition + 1);

        if (!isset($this->fieldMapperPlugins[$pluginKey])) {
            $this->punchoutLogger->logGenericErrorMessage(
                sprintf(PunchoutGatewayConfig::ERROR_FIELD_MAPPER_PLUGIN_NOT_REGISTERED, $pluginKey),
            );

            return null;
        }

        return $this->fieldMapperPlugins[$pluginKey]->getValue($mappingSourceTransfer, $fieldName);
    }
}
