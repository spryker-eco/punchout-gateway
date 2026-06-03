<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form\DataTransformer;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutFieldMappingRowFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutOciConfigurationFormType;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements \Symfony\Component\Form\DataTransformerInterface<array<string, array<string, mixed>>, array<string, array<string, mixed>>>
 */
class PunchoutOciMappingDataTransformer implements DataTransformerInterface
{
    public const string MAPPINGS = 'mappings';

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public function transform(mixed $value): array
    {
        $mapping = $value[PunchoutConnectionTransfer::MAPPINGS] ?? [];

        if (!$mapping) {
            return $value ?? [];
        }

        $mappingFields = [];

        foreach ($mapping as $ociField => $source) {
            $mappingFields[] = [
                PunchoutFieldMappingRowFormType::FIELD_CXML_FIELD => (string)$ociField,
                PunchoutFieldMappingRowFormType::FIELD_SOURCE => (string)($source ?? ''),
            ];
        }

        $value[PunchoutOciConfigurationFormType::MAPPING_FIELDS] = $mappingFields;

        unset($value[static::MAPPINGS]);

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function reverseTransform(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $mappings = [];

        foreach ((array)($value[PunchoutOciConfigurationFormType::MAPPING_FIELDS] ?? []) as $row) {
            $ociField = trim((string)($row[PunchoutFieldMappingRowFormType::FIELD_CXML_FIELD] ?? ''));

            if ($ociField === '') {
                continue;
            }

            $source = trim((string)($row[PunchoutFieldMappingRowFormType::FIELD_SOURCE] ?? ''));
            $mappings[$ociField] = $source !== '' ? $source : null;
        }

        $value[static::MAPPINGS] = $mappings;

        unset($value[PunchoutOciConfigurationFormType::MAPPING_FIELDS]);

        return $value;
    }
}
