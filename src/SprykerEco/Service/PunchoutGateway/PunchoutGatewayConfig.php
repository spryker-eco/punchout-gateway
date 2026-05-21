<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway;

use Spryker\Service\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;

class PunchoutGatewayConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return array<string>
     */
    public function getSupportedOciFields(): array
    {
        return array_keys($this->getDefaultOciFieldMap());
    }

    /**
     * @api
     *
     * Default field-map for OCI. Keys are OCI field names; values are source expressions or null to use built-in fallback.
     * Per-connection database mapping is merged on top — same key in database wins.
     *
     * @return array<string, string|null>
     */
    public function getDefaultOciFieldMap(): array
    {
        return [
            'NEW_ITEM-DESCRIPTION' => 'item.name',
            'NEW_ITEM-VENDORMAT' => 'item.sku',
            'NEW_ITEM-QUANTITY' => 'item.quantity',
            'NEW_ITEM-UNIT' => null,
            'NEW_ITEM-PRICE' => null,
            'NEW_ITEM-CURRENCY' => null,
            'NEW_ITEM-MATNR' => null,
            'NEW_ITEM-PRICEUNIT' => null,
            'NEW_ITEM-LEADTIME' => null,
            'NEW_ITEM-LONGTEXT' => null,
            'NEW_ITEM-VENDOR' => null,
            'NEW_ITEM-MANUFACTCODE' => null,
            'NEW_ITEM-MANUFACTMAT' => null,
            'NEW_ITEM-MATGROUP' => null,
            'NEW_ITEM-SERVICE' => null,
            'NEW_ITEM-CONTRACT' => null,
            'NEW_ITEM-CONTRACT_ITEM' => null,
            'NEW_ITEM-EXT_QUOTE_ID' => null,
            'NEW_ITEM-EXT_QUOTE_ITEM' => null,
            'NEW_ITEM-EXT_PRODUCT_ID' => null,
            'NEW_ITEM-ATTACHMENT' => null,
            'NEW_ITEM-ATTACHMENT_TITLE' => null,
            'NEW_ITEM-ATTACHMENT_PURPOSE' => null,
            'NEW_ITEM-EXT_SCHEMA_TYPE' => null,
            'NEW_ITEM-EXT_CATEGORY_ID' => null,
            'NEW_ITEM-EXT_CATEGORY' => null,
            'NEW_ITEM-SLD_SYS_NAME' => null,
            'NEW_ITEM-CUST_FIELD1' => null,
            'NEW_ITEM-CUST_FIELD2' => null,
            'NEW_ITEM-CUST_FIELD3' => null,
            'NEW_ITEM-CUST_FIELD4' => null,
            'NEW_ITEM-CUST_FIELD5' => null,
            'NEW_ITEM-ITEM_TYPE' => null,
            'NEW_ITEM-PARENT_ID' => null,
        ];
    }

    /**
     * @api
     *
     * @return list<string>
     */
    public function getExtrinsicBlackList(): array
    {
        return SharedPunchoutGatewayConfig::EXTRINSIC_BLACKLIST;
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSupportedCxmlFields(): array
    {
        return array_keys($this->getDefaultFieldMap());
    }

    /**
     * @api
     *
     * Default field-map used when a connection has no DB-level mapping configured.
     * Each key is a dot-separated cXML field path; each value is a source expression
     * (`PLUGIN_KEY.field.path`) or null to skip the field.
     * Per-connection database mapping is merged on top — same key in database wins.
     *
     * @return array<string, string|null>
     */
    public function getDefaultFieldMap(): array
    {
        return [
            // PunchOutOrderMessage header
            'attributes.language' => null,
            'attributes.buyerCookie' => null,
            'attributes.currency' => null,
            'attributes.operationAllowed' => null,

            // ItemID
            'ItemID.SupplierPartID' => 'item.sku',
            'ItemID.SupplierPartAuxiliaryID' => null,
            'ItemID.BuyerPartID' => 'item.groupKey',

            // ItemOut attributes
            'attributes.quantity' => 'item.quantity',
            'attributes.lineNumber' => null,
            'attributes.requestedDeliveryDate' => null,
            'attributes.agreementItemNumber' => null,
            'attributes.parentAgreementID' => null,
            'attributes.parentAgreementPayloadID' => null,
            'attributes.isAdHoc' => null,

            // ItemDetail
            'ItemDetail.UnitPrice' => 'item.unitPrice',
            'ItemDetail.Description' => 'item.name',
            'ItemDetail.UnitOfMeasure' => null,
            'ItemDetail.ManufacturerPartID' => null,
            'ItemDetail.ManufacturerName' => null,
            'ItemDetail.URL' => null,
            'ItemDetail.LeadTime' => null,
            'ItemDetail.ClassificationDomain' => null,
            'ItemDetail.ClassificationValue' => null,

            // Tax
            'Tax.Money' => null,
            'Tax.Description' => null,

            // Shipping
            'Shipping.Money' => null,
            'Shipping.Description' => null,

            // ShipTo
            'ShipTo.Address.Name' => null,
            'ShipTo.Address.PostalAddress.Street1' => null,
            'ShipTo.Address.PostalAddress.Street2' => null,
            'ShipTo.Address.PostalAddress.Street3' => null,
            'ShipTo.Address.PostalAddress.City' => null,
            'ShipTo.Address.PostalAddress.CountryCode' => null,
            'ShipTo.Address.PostalAddress.State' => null,
            'ShipTo.Address.PostalAddress.PostalCode' => null,

            // Outer cXML envelope
            'cXML.attributes.xml:lang' => null,
            'cXML.Header.From.Credential.domain' => null,
            'cXML.Header.From.Credential.Identity' => null,
            'cXML.Header.To.Credential.domain' => null,
            'cXML.Header.To.Credential.Identity' => null,
            'cXML.Header.Sender.Credential.domain' => null,
            'cXML.Header.Sender.Credential.Identity' => null,
            'cXML.Header.Sender.Credential.SharedSecret' => null,
            'cXML.Header.Sender.UserAgent' => null,
        ];
    }
}
