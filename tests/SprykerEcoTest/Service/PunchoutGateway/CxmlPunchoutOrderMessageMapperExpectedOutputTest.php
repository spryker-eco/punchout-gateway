<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Service\PunchoutGateway\Mapper;

use Codeception\Test\Unit;
use CXml\Model\CXml;
use CXml\Model\Message\PunchOutOrderMessage;
use CXml\Serializer;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MappingSourceTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\TaxTotalTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use SprykerEco\Service\PunchoutGateway\Dependency\Plugin\PunchoutFieldMapperPluginInterface;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoder;
use SprykerEco\Service\PunchoutGateway\Mapper\CxmlPunchoutOrderMessageMapper;
use SprykerEco\Service\PunchoutGateway\Mapper\Resolver\FieldValueResolver;
use SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper\ItemTransferFieldMapperPlugin;
use SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper\QuoteTransferFieldMapperPlugin;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;

/**
 * @group SprykerEcoTest
 * @group Service
 * @group PunchoutGateway
 * @group Mapper
 * @group CxmlPunchoutOrderMessageMapperExpectedOutputTest
 */
class CxmlPunchoutOrderMessageMapperExpectedOutputTest extends Unit
{
    protected const string BUYER_COOKIE = 'f5d75ddbc9e75b6346b36ee5c28c5e8b';

    protected const string FROM_IDENTITY = 'user@coupa.com';

    protected const string TO_IDENTITY = 'supplier@example.com';

    protected const string SHARED_SECRET = 's3cr3t';

    protected const string CURRENCY = 'USD';

    protected const string SUPPLIER_PART_ID = 'AM2692';

    protected const string SUPPLIER_PART_AUXILIARY_ID = 'A_B:5008937A_B:';

    protected const string DESCRIPTION = 'ANTI-RNase (15–30 U/ul)';

    protected const string MANUFACTURER_PART_ID = 'AM2692';

    protected const string MANUFACTURER_NAME = 'Acme Inc.';

    protected const string CLASSIFICATION_DOMAIN = 'UNSPSC';

    protected const string CLASSIFICATION_CODE = '41106104';

    protected const int QUANTITY = 1;

    protected const int UNIT_PRICE_CENT = 25000;

    protected const int TAX_CENT = 2188;

    protected const int SHIPPING_CENT = 0;

    protected const int TOTAL_CENT = 27188;

    protected const int TOTAL_CENT_NO_TAX = 27188 - self::TAX_CENT;

    protected const int LEAD_TIME = 0;

    // DB mapping override constants
    protected const string BUYER_COOKIE_OVERRIDE = 'overridden-cookie';

    protected const string CXML_LANG = 'de-DE';

    protected const string FROM_DOMAIN = 'test-from-domain';

    protected const string FROM_IDENTITY_OVERRIDE = 'test-from-identity';

    protected const string TO_DOMAIN = 'test-to-domain';

    protected const string TO_IDENTITY_OVERRIDE = 'test-to-identity';

    protected const string SENDER_DOMAIN = 'test-sender-domain';

    protected const string SENDER_IDENTITY = 'test-sender-identity';

    protected const string SENDER_SHARED_SECRET_OVERRIDE = 'test-shared-secret';

    protected const string USER_AGENT = 'test-user-agent';

    protected const string CURRENCY_OVERRIDE = 'EUR';

    protected const string OPERATION_OVERRIDE = 'create';

    protected const int TAX_CENT_OVERRIDE = 999;

    protected const string TAX_DESCRIPTION_OVERRIDE = 'Override-Tax-Label';

    protected const int SHIPPING_CENT_OVERRIDE = 123;

    protected const string SHIPPING_DESCRIPTION_OVERRIDE = 'Override-Shipping-Label';

    protected const string SHIP_TO_NAME = 'Override-Address-Name';

    protected const string SHIP_TO_STREET1 = 'Street-1';

    protected const string SHIP_TO_STREET2 = 'Street-2';

    protected const string SHIP_TO_STREET3 = 'Street-3';

    protected const string SHIP_TO_CITY = 'Override-City';

    protected const string SHIP_TO_COUNTRY = 'DE';

    protected const string SHIP_TO_STATE = 'BW';

    protected const string SHIP_TO_POSTAL_CODE = '10115';

    protected const string UNIT_OF_MEASURE_OVERRIDE = 'KG';

    public function testMapQuoteToCxmlReproducesProvidedPunchOutOrderMessage(): void
    {
        $xml = $this->createMapper()->mapQuoteToCxml($this->buildQuote());

        $this->assertNotSame('', $xml, 'Mapper returned empty cXML.');

        $cxml = $this->decodeCxml($xml);

        $payload = $cxml->message?->payload;
        $this->assertInstanceOf(PunchOutOrderMessage::class, $payload);

        $header = $payload->punchOutOrderMessageHeader;
        $item = $payload->getPunchoutOrderMessageItems()[0];
        $itemDetail = $item->itemDetail;
        $classification = $itemDetail->getClassifications()[0];

        // Envelope credentials and userAgent
        $this->assertSame(static::FROM_DOMAIN, $cxml->header->from->credential->domain);
        $this->assertSame(static::FROM_IDENTITY_OVERRIDE, $cxml->header->from->credential->identity);
        $this->assertSame(static::TO_DOMAIN, $cxml->header->to->credential->domain);
        $this->assertSame(static::TO_IDENTITY_OVERRIDE, $cxml->header->to->credential->identity);
        $this->assertSame(static::SENDER_DOMAIN, $cxml->header->sender->credential->domain);
        $this->assertSame(static::SENDER_IDENTITY, $cxml->header->sender->credential->identity);
        $this->assertSame(static::SENDER_SHARED_SECRET_OVERRIDE, $cxml->header->sender->credential->getSharedSecret());
        $this->assertSame(static::USER_AGENT, $cxml->header->sender->userAgent);
        $this->assertSame(static::CXML_LANG, $cxml->lang);

        // PunchOutOrderMessage attributes
        $this->assertSame(static::BUYER_COOKIE_OVERRIDE, $payload->buyerCookie);
        $this->assertSame(static::OPERATION_OVERRIDE, $header->getOperationAllowed());
        $this->assertSame(static::TOTAL_CENT_NO_TAX, $header->total->money->getValueCent());

        // Tax (overridden amount, description, and currency via attributes.currency)
        $this->assertNotNull($header->tax);
        $this->assertSame(static::TAX_CENT_OVERRIDE, $header->tax->money->getValueCent());
        $this->assertSame(static::TAX_DESCRIPTION_OVERRIDE, $header->tax->description->value);
        $this->assertSame(static::CURRENCY_OVERRIDE, $header->tax->money->currency);

        // Shipping (overridden amount and description)
        $this->assertNotNull($header->shipping);
        $this->assertSame(static::SHIPPING_CENT_OVERRIDE, $header->shipping->money->getValueCent());
        $this->assertSame(static::SHIPPING_DESCRIPTION_OVERRIDE, $header->shipping->description->value);

        // ShipTo
        $shipTo = $header->getShipTo();
        $this->assertNotNull($shipTo);
        $this->assertSame(static::SHIP_TO_NAME, $shipTo->address->name->value);
        $this->assertSame(
            [static::SHIP_TO_STREET1, static::SHIP_TO_STREET2, static::SHIP_TO_STREET3],
            $shipTo->address->postalAddress->street,
        );
        $this->assertSame(static::SHIP_TO_CITY, $shipTo->address->postalAddress->city);
        $this->assertSame(static::SHIP_TO_COUNTRY, $shipTo->address->postalAddress->country->isoCountryCode);
        $this->assertSame(static::SHIP_TO_STATE, $shipTo->address->postalAddress->state);
        $this->assertSame(static::SHIP_TO_POSTAL_CODE, $shipTo->address->postalAddress->postalCode);

        // ItemIn / ItemID
        $this->assertSame(static::QUANTITY, $item->quantity);
        $this->assertSame(static::SUPPLIER_PART_ID, $item->itemId->supplierPartId);
        $this->assertSame(static::SUPPLIER_PART_AUXILIARY_ID, $item->itemId->supplierPartAuxiliaryId);

        // ItemDetail
        $this->assertSame(static::DESCRIPTION, $itemDetail->descriptions[0]->value);
        $this->assertSame(static::UNIT_OF_MEASURE_OVERRIDE, $itemDetail->unitOfMeasure);
        $this->assertSame(static::UNIT_PRICE_CENT, $itemDetail->unitPrice->money->getValueCent());
        $this->assertSame(static::MANUFACTURER_PART_ID, $itemDetail->getManufacturerPartId());
        $this->assertSame(static::MANUFACTURER_NAME, $itemDetail->getManufacturerName());
        $this->assertSame(static::LEAD_TIME, $itemDetail->getLeadtime());

        // Classification (UNSPSC)
        $this->assertSame(static::CLASSIFICATION_DOMAIN, $classification->domain);
        $this->assertSame(static::CLASSIFICATION_CODE, $classification->value);
    }

    protected function decodeCxml(string $xml): CXml
    {
        $cxml = Serializer::create()->deserialize($xml);

        $this->assertInstanceOf(CXml::class, $cxml);

        return $cxml;
    }

    protected function createMapper(): CxmlPunchoutOrderMessageMapper
    {
        return new CxmlPunchoutOrderMessageMapper(
            new CxmlEncoder(Serializer::create()),
            new PunchoutLogger(),
            new PunchoutGatewayConfig(),
            new FieldValueResolver(
                [
                    'item' => new ItemTransferFieldMapperPlugin(),
                    'quote' => new QuoteTransferFieldMapperPlugin(),
                    'itemAttr' => $this->createItemAttributesFieldMapperPlugin(),
                    'lit' => $this->createLiteralFieldMapperPlugin(),
                ],
                new PunchoutLogger(),
            ),
        );
    }

    protected function createItemAttributesFieldMapperPlugin(): PunchoutFieldMapperPluginInterface
    {
        return new class implements PunchoutFieldMapperPluginInterface {
            public function getPossibleValues(): array
            {
                return [];
            }

            public function getValue(MappingSourceTransfer $mappingSourceTransfer, string $fieldName): mixed
            {
                return $mappingSourceTransfer->getItem()?->getConcreteAttributes()[$fieldName] ?? null;
            }
        };
    }

    protected function createLiteralFieldMapperPlugin(): PunchoutFieldMapperPluginInterface
    {
        return new class implements PunchoutFieldMapperPluginInterface {
            public function getPossibleValues(): array
            {
                return [];
            }

            public function getValue(MappingSourceTransfer $mappingSourceTransfer, string $fieldName): mixed
            {
                return $fieldName;
            }
        };
    }

    protected function buildQuote(): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode(static::CURRENCY))
            ->setPunchoutSession($this->buildSession())
            ->setTotals(
                (new TotalsTransfer())
                    ->setExpenseTotal(static::SHIPPING_CENT)
                    ->setTaxTotal((new TaxTotalTransfer())->setAmount(static::TAX_CENT))
                    ->setGrandTotal(static::TOTAL_CENT),
            )
            ->addExpense(
                (new ExpenseTransfer())
                    ->setType(SharedPunchoutGatewayConfig::SHIPMENT_EXPENSE_TYPE)
                    ->setSumGrossPrice(static::SHIPPING_CENT),
            )
            ->setShippingAddress(new AddressTransfer())
            ->addItem(
                (new ItemTransfer())
                    ->setSku(static::SUPPLIER_PART_ID)
                    ->setGroupKey(static::SUPPLIER_PART_ID)
                    ->setQuantity(static::QUANTITY)
                    ->setName(static::DESCRIPTION)
                    ->setUnitPrice(static::UNIT_PRICE_CENT)
                    ->setConcreteAttributes([
                        'supplierId' => static::SUPPLIER_PART_AUXILIARY_ID,
                        'manufacturerName' => static::MANUFACTURER_NAME,
                        'leadTime' => static::LEAD_TIME,
                        'classificationDomain' => static::CLASSIFICATION_DOMAIN,
                        'classificationValue' => static::CLASSIFICATION_CODE,
                    ]),
            );
    }

    protected function buildSession(): PunchoutSessionTransfer
    {
        $cxmlSetupRequest = (new PunchoutCxmlSetupRequestTransfer())
            ->setFromIdentity(static::FROM_IDENTITY)
            ->setToIdentity(static::TO_IDENTITY)
            ->setSenderSharedSecret(static::SHARED_SECRET)
            ->setExtrinsicFields([]);

        $connection = (new PunchoutConnectionTransfer())
            ->setMappings([
                    // Envelope
                    'cXML.attributes.xml:lang' => sprintf('lit.%s', static::CXML_LANG),
                    'cXML.Header.From.Credential.domain' => sprintf('lit.%s', static::FROM_DOMAIN),
                    'cXML.Header.From.Credential.Identity' => sprintf('lit.%s', static::FROM_IDENTITY_OVERRIDE),
                    'cXML.Header.To.Credential.domain' => sprintf('lit.%s', static::TO_DOMAIN),
                    'cXML.Header.To.Credential.Identity' => sprintf('lit.%s', static::TO_IDENTITY_OVERRIDE),
                    'cXML.Header.Sender.Credential.domain' => sprintf('lit.%s', static::SENDER_DOMAIN),
                    'cXML.Header.Sender.Credential.Identity' => sprintf('lit.%s', static::SENDER_IDENTITY),
                    'cXML.Header.Sender.Credential.SharedSecret' => sprintf('lit.%s', static::SENDER_SHARED_SECRET_OVERRIDE),
                    'cXML.Header.Sender.UserAgent' => sprintf('lit.%s', static::USER_AGENT),
                    // PunchOutOrderMessage attributes
                    'attributes.buyerCookie' => sprintf('lit.%s', static::BUYER_COOKIE_OVERRIDE),
                    'attributes.currency' => sprintf('lit.%s', static::CURRENCY_OVERRIDE),
                    'attributes.operationAllowed' => sprintf('lit.%s', static::OPERATION_OVERRIDE),
                    // Tax
                    'Tax.Money' => sprintf('lit.%d', static::TAX_CENT_OVERRIDE),
                    'Tax.Description' => sprintf('lit.%s', static::TAX_DESCRIPTION_OVERRIDE),
                    // Shipping
                    'Shipping.Money' => sprintf('lit.%d', static::SHIPPING_CENT_OVERRIDE),
                    'Shipping.Description' => sprintf('lit.%s', static::SHIPPING_DESCRIPTION_OVERRIDE),
                    // ShipTo
                    'ShipTo.Address.Name' => sprintf('lit.%s', static::SHIP_TO_NAME),
                    'ShipTo.Address.PostalAddress.Street1' => sprintf('lit.%s', static::SHIP_TO_STREET1),
                    'ShipTo.Address.PostalAddress.Street2' => sprintf('lit.%s', static::SHIP_TO_STREET2),
                    'ShipTo.Address.PostalAddress.Street3' => sprintf('lit.%s', static::SHIP_TO_STREET3),
                    'ShipTo.Address.PostalAddress.City' => sprintf('lit.%s', static::SHIP_TO_CITY),
                    'ShipTo.Address.PostalAddress.CountryCode' => sprintf('lit.%s', static::SHIP_TO_COUNTRY),
                    'ShipTo.Address.PostalAddress.State' => sprintf('lit.%s', static::SHIP_TO_STATE),
                    'ShipTo.Address.PostalAddress.PostalCode' => sprintf('lit.%s', static::SHIP_TO_POSTAL_CODE),
                    // ItemDetail
                    'ItemDetail.UnitOfMeasure' => sprintf('lit.%s', static::UNIT_OF_MEASURE_OVERRIDE),
                    // Item fields via itemAttr plugin
                    'ItemID.SupplierPartAuxiliaryID' => 'itemAttr.supplierId',
                    'ItemDetail.ManufacturerPartID' => 'item.sku',
                    'ItemDetail.ManufacturerName' => 'itemAttr.manufacturerName',
                    'ItemDetail.LeadTime' => 'itemAttr.leadTime',
                    'ItemDetail.ClassificationDomain' => 'itemAttr.classificationDomain',
                    'ItemDetail.ClassificationValue' => 'itemAttr.classificationValue',
                ]);

        return (new PunchoutSessionTransfer())
            ->setBuyerCookie(static::BUYER_COOKIE)
            ->setOperation(SharedPunchoutGatewayConfig::OPERATION_EDIT)
            ->setConnection($connection)
            ->setPunchoutData((new PunchoutSessionDataTransfer())->setCxmlSetupRequest($cxmlSetupRequest));
    }
}
