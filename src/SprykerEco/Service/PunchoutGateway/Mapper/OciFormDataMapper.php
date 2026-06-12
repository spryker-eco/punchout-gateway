<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Mapper;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MappingSourceTransfer;
use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\Mapper\Resolver\MappingFieldResolverInterface;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;

class OciFormDataMapper implements OciFormDataMapperInterface
{
    protected const string FIELD_TARGET = '~TARGET';

    /**
     * @var array<string>
     */
    protected const array SAP_ECHO_FIELDS = ['~OkCode', '~TARGET', '~CALLER'];

    protected const string OCI_FIELD_LONGTEXT = 'NEW_ITEM-LONGTEXT';

    /**
     * @var array<string>
     */
    protected const array REQUIRED_OCI_FIELDS = [
        'NEW_ITEM-DESCRIPTION',
        'NEW_ITEM-VENDORMAT',
        'NEW_ITEM-QUANTITY',
        'NEW_ITEM-UNIT',
        'NEW_ITEM-PRICE',
        'NEW_ITEM-CURRENCY',
    ];

    public function __construct(
        protected PunchoutGatewayConfig $config,
        protected MappingFieldResolverInterface $mappingFieldResolver,
    ) {
    }

    public function mapOciFormData(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer
    {
        $punchoutSession = $quoteTransfer->getPunchoutSession();

        if ($punchoutSession === null) {
            return null;
        }

        $actionUrl = $punchoutSession->getBrowserFormPostUrl();

        if ($actionUrl === null || $actionUrl === '') {
            return null;
        }

        $ociLoginRequest = $punchoutSession->getPunchoutData()?->getOciLoginRequest();

        if ($ociLoginRequest === null) {
            return null;
        }

        $fieldMap = $quoteTransfer->getPunchoutSession()
            ?->getConnection()
            ?->getMappings() ?? [];
        $formData = (new PunchoutFormDataTransfer())->setActionUrl($actionUrl);

        $this->setFormTarget($formData, $ociLoginRequest);
        $this->addItemFields($formData, $quoteTransfer, $fieldMap);
        $this->addSapEchoFields($formData, $ociLoginRequest);

        return $formData;
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function addItemFields(
        PunchoutFormDataTransfer $formData,
        QuoteTransfer $quoteTransfer,
        array $fieldMap,
    ): void {
        $currencyCode = (string)$quoteTransfer->getCurrency()?->getCode();
        $fractionDigits = $quoteTransfer->getCurrency()?->getFractionDigits() ?? 2;
        $index = 1;

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $source = $this->mappingFieldResolver->buildMappingSource($quoteTransfer, $itemTransfer);
            $this->addSingleItemFields($formData, $itemTransfer, $source, $index, $fieldMap, $currencyCode, $fractionDigits);
            $index++;
        }
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function addSingleItemFields(
        PunchoutFormDataTransfer $formData,
        ItemTransfer $itemTransfer,
        MappingSourceTransfer $source,
        int $index,
        array $fieldMap,
        string $currencyCode,
        int $fractionDigits,
    ): void {
        $formData->addField(
            sprintf('NEW_ITEM-DESCRIPTION[%d]', $index),
            (string)$this->mappingFieldResolver->resolveWithFallback($fieldMap, 'NEW_ITEM-DESCRIPTION', $source, fn () => $itemTransfer->getName()),
        );

        $formData->addField(
            sprintf('NEW_ITEM-VENDORMAT[%d]', $index),
            (string)$this->mappingFieldResolver->resolveWithFallback($fieldMap, 'NEW_ITEM-VENDORMAT', $source, fn () => $itemTransfer->getSku()),
        );

        $formData->addField(
            sprintf('NEW_ITEM-QUANTITY[%d]', $index),
            (string)$this->mappingFieldResolver->resolveWithFallback($fieldMap, 'NEW_ITEM-QUANTITY', $source, fn () => $itemTransfer->getQuantity()),
        );

        $formData->addField(
            sprintf('NEW_ITEM-UNIT[%d]', $index),
            (string)$this->mappingFieldResolver->resolveWithFallback($fieldMap, 'NEW_ITEM-UNIT', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_UNIT_OF_MEASURE),
        );

        $formData->addField(
            sprintf('NEW_ITEM-PRICE[%d]', $index),
            $this->resolvePrice($fieldMap, $source, $itemTransfer, $fractionDigits),
        );

        $formData->addField(
            sprintf('NEW_ITEM-CURRENCY[%d]', $index),
            (string)$this->mappingFieldResolver->resolveWithFallback($fieldMap, 'NEW_ITEM-CURRENCY', $source, fn () => $currencyCode),
        );

        $this->addOptionalItemFields($formData, $source, $index, $fieldMap);
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function addOptionalItemFields(
        PunchoutFormDataTransfer $formData,
        MappingSourceTransfer $source,
        int $index,
        array $fieldMap,
    ): void {
        foreach ($fieldMap as $ociField => $expression) {
            if (in_array($ociField, static::REQUIRED_OCI_FIELDS, true)) {
                continue;
            }

            $resolved = $this->mappingFieldResolver->resolveOrSkip($fieldMap, $ociField, $source);

            if ($resolved === null) {
                continue;
            }

            if ($ociField === static::OCI_FIELD_LONGTEXT) {
                $formData->addField(sprintf('NEW_ITEM-LONGTEXT_%d:132[]', $index), (string)$resolved);

                continue;
            }

            $formData->addField(sprintf('%s[%d]', $ociField, $index), (string)$resolved);
        }
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function resolvePrice(
        array $fieldMap,
        MappingSourceTransfer $source,
        ItemTransfer $itemTransfer,
        int $fractionDigits,
    ): string {
        $centAmount = (int)$this->mappingFieldResolver->resolveWithFallback(
            $fieldMap,
            'NEW_ITEM-PRICE',
            $source,
            fn () => $itemTransfer->getUnitPrice(),
        );

        return $this->formatPrice($centAmount, $fractionDigits);
    }

    protected function formatPrice(int $centAmount, int $fractionDigits): string
    {
        return number_format($centAmount / (10 ** $fractionDigits), 3, '.', '');
    }

    protected function setFormTarget(
        PunchoutFormDataTransfer $formData,
        PunchoutOciLoginRequestTransfer $ociLoginRequest,
    ): void {
        $loginFormData = $ociLoginRequest->getFormData();

        if (!isset($loginFormData[static::FIELD_TARGET])) {
            return;
        }

        $formData->setFormTarget($loginFormData[static::FIELD_TARGET]);
    }

    protected function addSapEchoFields(
        PunchoutFormDataTransfer $formData,
        PunchoutOciLoginRequestTransfer $ociLoginRequest,
    ): void {
        $loginFormData = $ociLoginRequest->getFormData();

        foreach (static::SAP_ECHO_FIELDS as $fieldName) {
            if (!isset($loginFormData[$fieldName])) {
                continue;
            }

            $formData->addField($fieldName, $loginFormData[$fieldName]);
        }
    }
}
