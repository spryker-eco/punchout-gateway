<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form\DataTransformer;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCxmlConfigurationFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutExtrinsicMappingRowFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutFieldMappingRowFormType;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements \Symfony\Component\Form\DataTransformerInterface<array<string, array<string, mixed>>, array<string, array<string, mixed>>>
 */
class PunchoutCxmlMappingDataTransformer implements DataTransformerInterface
{
    public const string MAPPINGS = 'mappings';

    /**
     * Converts flat assoc map to {PunchoutCxmlConfigurationFormType::MAPPING_FIELDS: [...], PunchoutCxmlConfigurationFormType::MAPPING_EXTRINSICS: [...]}
     *
     * @return array<string, array<int|string, mixed>>
     */
    public function transform(mixed $value): array
    {
        $mapping = $value[PunchoutConnectionTransfer::MAPPINGS] ?? [];

        if (!$mapping) {
            return $value ?? [];
        }

        $mappingExtrinsics = [];
        $mappingFields = [];

        foreach ($mapping as $cxmlPath => $source) {
            $source = (string)($source ?? '');

            if (str_starts_with((string)$cxmlPath, SharedPunchoutGatewayConfig::EXTRINSIC_PREFIX)) {
                $mappingExtrinsics[] = [
                    PunchoutExtrinsicMappingRowFormType::FIELD_EXTRINSIC_NAME => substr((string)$cxmlPath, strlen(SharedPunchoutGatewayConfig::EXTRINSIC_PREFIX)),
                    PunchoutExtrinsicMappingRowFormType::FIELD_SOURCE => $source,
                ];

                continue;
            }

            $mappingFields[] = [
                PunchoutFieldMappingRowFormType::FIELD_CXML_FIELD => (string)$cxmlPath,
                PunchoutExtrinsicMappingRowFormType::FIELD_SOURCE => $source,
            ];
        }

        $value[PunchoutCxmlConfigurationFormType::MAPPING_FIELDS] = $mappingFields;
        $value[PunchoutCxmlConfigurationFormType::MAPPING_EXTRINSICS] = $mappingExtrinsics;

        unset($value[static::MAPPINGS]);

        return $value;
    }

    /**
     * Converts {PunchoutCxmlConfigurationFormType::MAPPING_FIELDS: [...], PunchoutCxmlConfigurationFormType::MAPPING_EXTRINSICS: [...]} back to flat assoc map.
     *
     * @return array<string, mixed>
     */
    public function reverseTransform(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $mappings = [];

        foreach ((array)($value[PunchoutCxmlConfigurationFormType::MAPPING_FIELDS] ?? []) as $row) {
            $cxmlField = trim((string)($row[PunchoutFieldMappingRowFormType::FIELD_CXML_FIELD] ?? ''));

            if ($cxmlField === '') {
                continue;
            }

            $mappings[$cxmlField] = trim((string)($row[PunchoutExtrinsicMappingRowFormType::FIELD_SOURCE] ?? ''));
        }

        foreach ((array)($value[PunchoutCxmlConfigurationFormType::MAPPING_EXTRINSICS] ?? []) as $row) {
            $extrinsicName = trim((string)($row[PunchoutExtrinsicMappingRowFormType::FIELD_EXTRINSIC_NAME] ?? ''));

            if ($extrinsicName === '') {
                continue;
            }

            $cxmlPath = SharedPunchoutGatewayConfig::EXTRINSIC_PREFIX . $extrinsicName;
            $mappings[$cxmlPath] = trim((string)($row[PunchoutExtrinsicMappingRowFormType::FIELD_SOURCE] ?? ''));
        }

        $value[static::MAPPINGS] = $mappings;

        unset($value[PunchoutCxmlConfigurationFormType::MAPPING_EXTRINSICS], $value[PunchoutCxmlConfigurationFormType::MAPPING_FIELDS]);

        return $value;
    }
}
