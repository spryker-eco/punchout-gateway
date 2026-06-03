<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway\Mapper;

use CXml\Builder;
use CXml\Builder\PunchOutOrderMessageBuilder;
use CXml\Model\Classification;
use CXml\Model\Country;
use CXml\Model\Credential;
use CXml\Model\Description;
use CXml\Model\ItemDetail;
use CXml\Model\ItemId;
use CXml\Model\ItemIn;
use CXml\Model\Message\PunchOutOrderMessage;
use CXml\Model\MoneyWrapper;
use CXml\Model\PostalAddress;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MappingSourceTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoderInterface;
use SprykerEco\Service\PunchoutGateway\Mapper\Resolver\FieldValueResolverInterface;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;

class CxmlPunchoutOrderMessageMapper implements CxmlPunchoutOrderMessageMapperInterface
{
    protected const string CLASSIFICATION_UNIT_OF_MEASURE = 'UNSPSC';

    protected const string ITEM_DETAIL_EXTRINSIC_PREFIX = 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Extrinsic.';

    public function __construct(
        protected CxmlEncoderInterface $cxmlEncoder,
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayConfig $config,
        protected FieldValueResolverInterface $fieldValueResolver,
    ) {
    }

    public function mapQuoteToCxml(QuoteTransfer $quoteTransfer): string
    {
        $punchoutSessionTransfer = $quoteTransfer->getPunchoutSession();

        if ($punchoutSessionTransfer === null) {
            return '';
        }

        if ($quoteTransfer->getItems()->count() === 0) {
            return '';
        }

        $cxmlSetupRequest = $this->resolveCxmlSetupRequest($punchoutSessionTransfer);

        if ($cxmlSetupRequest === null) {
            return '';
        }

        $fieldMap = $quoteTransfer->getPunchoutSession()
            ?->getConnection()
            ?->getMappings() ?? [];
        $source = $this->buildMappingSource($quoteTransfer);

        $punchoutOrderMessage = $this->buildPunchoutOrderMessage($quoteTransfer, $punchoutSessionTransfer, $fieldMap, $source);

        $language = $this->resolveWithFallback($fieldMap, 'cXML.attr.xml:lang', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_LANGUAGE);
        $fromDomain = $this->resolveWithFallback($fieldMap, 'cXML.Header.From.Credential.attr.domain', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_CREDENTIAL_DOMAIN);
        $fromIdentity = $this->resolveWithFallback($fieldMap, 'cXML.Header.From.Credential.Identity', $source, fn () => (string)$cxmlSetupRequest->getToIdentity());
        $toDomain = $this->resolveWithFallback($fieldMap, 'cXML.Header.To.Credential.attr.domain', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_CREDENTIAL_DOMAIN);
        $toIdentity = $this->resolveWithFallback($fieldMap, 'cXML.Header.To.Credential.Identity', $source, fn () => (string)$cxmlSetupRequest->getFromIdentity());
        $senderDomain = $this->resolveWithFallback($fieldMap, 'cXML.Header.Sender.Credential.attr.domain', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_CREDENTIAL_DOMAIN);
        $senderIdentity = $this->resolveWithFallback($fieldMap, 'cXML.Header.Sender.Credential.Identity', $source, fn () => (string)$cxmlSetupRequest->getToIdentity());
        $senderSharedSecret = $this->resolveOrSkip($fieldMap, 'cXML.Header.Sender.Credential.SharedSecret', $source) ?? $cxmlSetupRequest->getSenderSharedSecret();
        $userAgent = $this->resolveWithFallback($fieldMap, 'cXML.Header.Sender.UserAgent', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_SENDER_USER_AGENT);

        $cxml = Builder::create((string)$userAgent, (string)$language)
            ->from(new Credential((string)$fromDomain, (string)$fromIdentity))
            ->to(new Credential((string)$toDomain, (string)$toIdentity))
            ->sender(
                (new Credential((string)$senderDomain, (string)$senderIdentity))
                    ->setSharedSecret($senderSharedSecret),
            )
            ->payload($punchoutOrderMessage)
            ->build();

        return $this->cxmlEncoder->encodeCxml($cxml);
    }

    protected function resolveCxmlSetupRequest(PunchoutSessionTransfer $punchoutSessionTransfer): ?PunchoutCxmlSetupRequestTransfer
    {
        $punchoutData = $punchoutSessionTransfer->getPunchoutData();

        if ($punchoutData === null || $punchoutData->getCxmlSetupRequest() === null) {
            $this->punchoutLogger->logGenericErrorMessage(SharedPunchoutGatewayConfig::ERROR_MISSING_CXML_SETUP_REQUEST);

            return null;
        }

        return $punchoutData->getCxmlSetupRequest();
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function buildPunchoutOrderMessage(
        QuoteTransfer $quoteTransfer,
        PunchoutSessionTransfer $punchoutSessionTransfer,
        array $fieldMap,
        MappingSourceTransfer $mappingSourceTransfer,
    ): PunchOutOrderMessage {
        // Cross-cutting: builder propagates xml:lang to every Description/MultilanguageString element.
        $language = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.attr.xml:lang', $mappingSourceTransfer, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_LANGUAGE);
        $buyerCookie = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.BuyerCookie', $mappingSourceTransfer, fn () => (string)$punchoutSessionTransfer->getBuyerCookie());
        // Cross-cutting: builder propagates currency to every Money element (Total, Shipping, Tax, UnitPrice).
        $currency = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.attr.currency', $mappingSourceTransfer, fn () => (string)$quoteTransfer->getCurrency()?->getCode());
        $operation = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.attr.operationAllowed', $mappingSourceTransfer) ?? $punchoutSessionTransfer->getOperation();

        $builder = PunchOutOrderMessageBuilder::create(
            (string)$language,
            (string)$buyerCookie,
            (string)$currency,
            $operation !== null ? (string)$operation : null,
        );

        $extrinsics = $punchoutSessionTransfer->getPunchoutData()->getCxmlSetupRequest()->getExtrinsicFields();

        $this->addItems($builder, $quoteTransfer, $extrinsics, $fieldMap, (string)$language, (string)$currency);
        $this->addShipTo($builder, $quoteTransfer, $fieldMap, $mappingSourceTransfer);
        $this->addShippingCost($builder, $quoteTransfer, $fieldMap, $mappingSourceTransfer);
        $this->addTax($builder, $quoteTransfer, $fieldMap, $mappingSourceTransfer);

        return $builder->build();
    }

    /**
     * @param array<string, string> $extrinsics
     * @param array<string, string|null> $fieldMap
     */
    protected function addItems(
        PunchOutOrderMessageBuilder $builder,
        QuoteTransfer $quoteTransfer,
        array $extrinsics,
        array $fieldMap,
        string $language,
        string $currency
    ): void {
        $extrinsics = $this->filterExtrinsics($extrinsics);

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $this->addItem($builder, $itemTransfer, $extrinsics, $fieldMap, $quoteTransfer, $language, $currency);
        }
    }

    /**
     * @param array<string, string> $extrinsics
     *
     * @return array<string, string>
     */
    protected function filterExtrinsics(array $extrinsics): array
    {
        return array_diff_key(
            $extrinsics,
            array_flip($this->config->getExtrinsicBlackList()),
        );
    }

    protected function buildMappingSource(QuoteTransfer $quoteTransfer, ?ItemTransfer $itemTransfer = null): MappingSourceTransfer
    {
        $mappingSourceTransfer = new MappingSourceTransfer();
        $mappingSourceTransfer->setQuote($quoteTransfer);

        if ($itemTransfer !== null) {
            $mappingSourceTransfer->setItem($itemTransfer);
        }

        return $mappingSourceTransfer;
    }

    /**
     * @param array<string, string> $extrinsics
     * @param array<string, string|null> $fieldMap
     */
    protected function addItem(
        PunchOutOrderMessageBuilder $builder,
        ItemTransfer $itemTransfer,
        array $extrinsics,
        array $fieldMap,
        QuoteTransfer $quoteTransfer,
        string $language,
        string $currency,
    ): void {
        $source = $this->buildMappingSource($quoteTransfer, $itemTransfer);

        $itemId = new ItemId(
            $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemID.SupplierPartID', $source, fn () => (string)$itemTransfer->getSku()),
            $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemID.SupplierPartAuxiliaryID', $source),
            $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemID.BuyerPartID', $source) ?? $itemTransfer->getGroupKey(),
        );

        $quantity = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.attr.quantity', $source, fn () => (int)$itemTransfer->getQuantity());
        $description = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Description', $source, fn () => (string)$itemTransfer->getName());
        $unitOfMeasure = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.UnitOfMeasure', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_UNIT_OF_MEASURE);
        $unitPrice = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.UnitPrice.Money', $source, fn () => (int)$itemTransfer->getUnitPrice());
        $manufacturerPartId = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.ManufacturerPartID', $source);
        $manufacturerName = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.ManufacturerName', $source);
        $url = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.URL', $source);
        $leadTime = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.LeadTime', $source);
        $classificationDomain = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Classification.attr.domain', $source, fn () => static::CLASSIFICATION_UNIT_OF_MEASURE);
        $classificationValue = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Classification', $source, fn () => '');

        $resolvedExtrinsics = $this->resolveExtrinsics($extrinsics, $fieldMap, $source);

        $itemDetail = ItemDetail::create(
            new Description((string)$description, null, $language),
            (string)$unitOfMeasure,
            new MoneyWrapper($currency, (int)$unitPrice),
            [new Classification((string)$classificationDomain, (string)$classificationValue)],
        )
            ->setManufacturerPartId($manufacturerPartId !== null ? (string)$manufacturerPartId : null)
            ->setManufacturerName($manufacturerName !== null ? (string)$manufacturerName : null)
            ->setUrl($url !== null ? (string)$url : null)
            ->setLeadtime($leadTime !== null ? (int)$leadTime : null);

        foreach ($resolvedExtrinsics as $extrinsicKey => $extrinsicValue) {
            $itemDetail->addExtrinsicAsKeyValue($extrinsicKey, $extrinsicValue);
        }

        $builder->addItem(ItemIn::create((int)$quantity, $itemId, $itemDetail));
    }

    /**
     * Resolves a required field: falls back to $fallback() when the map has null or expression resolves to null.
     *
     * @param array<string, string|null> $fieldMap
     */
    protected function resolveWithFallback(array $fieldMap, string $key, MappingSourceTransfer $source, callable $fallback): mixed
    {
        if (!array_key_exists($key, $fieldMap) || $fieldMap[$key] === null || $fieldMap[$key] === '') {
            return $fallback();
        }

        $resolved = $this->fieldValueResolver->resolve($fieldMap[$key], $source);

        return $resolved ?? $fallback();
    }

    /**
     * Resolves an optional field: returns null when the map has null or expression resolves to null.
     *
     * @param array<string, string|null> $fieldMap
     */
    protected function resolveOrSkip(array $fieldMap, string $key, MappingSourceTransfer $source): mixed
    {
        if (!array_key_exists($key, $fieldMap) || $fieldMap[$key] === null || $fieldMap[$key] === '') {
            return null;
        }

        return $this->fieldValueResolver->resolve($fieldMap[$key], $source);
    }

    /**
     * Merges session extrinsics with per-key map overrides.
     * Map entry null = remove that key from session batch.
     * Map entry expression = resolve per-item and override/add.
     *
     * @param array<string, string> $sessionExtrinsics
     * @param array<string, string|null> $fieldMap
     *
     * @return array<string, string>
     */
    protected function resolveExtrinsics(array $sessionExtrinsics, array $fieldMap, MappingSourceTransfer $source): array
    {
        $result = $sessionExtrinsics;

        foreach ($fieldMap as $cxmlKey => $expression) {
            if (!str_starts_with($cxmlKey, static::ITEM_DETAIL_EXTRINSIC_PREFIX)) {
                continue;
            }

            $extrinsicName = substr($cxmlKey, strlen(static::ITEM_DETAIL_EXTRINSIC_PREFIX));

            if ($expression === null || $expression === '') {
                unset($result[$extrinsicName]);

                continue;
            }

            $resolvedValue = $this->fieldValueResolver->resolve($expression, $source);

            if ($resolvedValue !== null) {
                $result[$extrinsicName] = (string)$resolvedValue;

                continue;
            }

            unset($result[$extrinsicName]);
        }

        return $result;
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function addShipTo(
        PunchOutOrderMessageBuilder $builder,
        QuoteTransfer $quoteTransfer,
        array $fieldMap,
        MappingSourceTransfer $source,
    ): void {
        $address = $quoteTransfer->getShippingAddress();

        if ($address === null) {
            return;
        }

        $street1 = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Street1', $source) ?? $address->getAddress1();
        $street2 = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Street2', $source) ?? $address->getAddress2();
        $street3 = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Street3', $source) ?? $address->getAddress3();
        $city = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.City', $source, fn () => (string)$address->getCity());
        $countryCode = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Country.attr.isoCountryCode', $source, fn () => (string)$address->getIso2Code());
        $state = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.State', $source) ?? $address->getRegion() ?? $address->getState();
        $postalCode = $this->resolveOrSkip($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.PostalCode', $source) ?? $address->getZipCode();

        $streetLines = array_values(array_filter([$street1, $street2, $street3]));

        $postalAddress = new PostalAddress(
            [],
            $streetLines,
            (string)$city,
            new Country((string)$countryCode),
            null,
            $state,
            $postalCode,
        );

        $defaultName = trim(sprintf('%s %s', $address->getFirstName(), $address->getLastName()));
        $addressName = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.attr.name', $source, fn () => $defaultName ?: 'Ship To');

        $builder->shipTo((string)$addressName, $postalAddress);
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function addShippingCost(
        PunchOutOrderMessageBuilder $builder,
        QuoteTransfer $quoteTransfer,
        array $fieldMap,
        MappingSourceTransfer $source,
    ): void {
        $totals = $quoteTransfer->getTotals();

        if ($totals === null || $totals->getExpenseTotal() === null) {
            return;
        }

        foreach ($quoteTransfer->getExpenses() as $expenseTransfer) {
            if ($expenseTransfer->getType() !== SharedPunchoutGatewayConfig::SHIPMENT_EXPENSE_TYPE) {
                continue;
            }

            $amount = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Shipping.Money', $source, fn () => (int)$expenseTransfer->getSumGrossPrice());
            $description = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Shipping.Description', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_SHIPPING_DESCRIPTION);

            $builder->shipping((int)$amount, (string)$description);

            break;
        }
    }

    /**
     * @param array<string, string|null> $fieldMap
     */
    protected function addTax(
        PunchOutOrderMessageBuilder $builder,
        QuoteTransfer $quoteTransfer,
        array $fieldMap,
        MappingSourceTransfer $source,
    ): void {
        $totals = $quoteTransfer->getTotals();

        if ($totals === null || $totals->getTaxTotal() === null || $totals->getTaxTotal()->getAmount() === null) {
            return;
        }

        $amount = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Tax.Money', $source, fn () => (int)$totals->getTaxTotal()->getAmount());
        $description = $this->resolveWithFallback($fieldMap, 'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Tax.Description', $source, fn () => SharedPunchoutGatewayConfig::DEFAULT_CXML_TAX_DESCRIPTION);

        $builder->tax((int)$amount, (string)$description);
    }
}
