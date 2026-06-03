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
    public function getNonAutocompleteTransferFieldPrefixes(): array
    {
        return ['spy'];
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
    public function getSupportedOciFields(): array
    {
        return [
            'NEW_ITEM-DESCRIPTION',
            'NEW_ITEM-VENDORMAT',
            'NEW_ITEM-QUANTITY',
            'NEW_ITEM-UNIT',
            'NEW_ITEM-PRICE',
            'NEW_ITEM-CURRENCY',
            'NEW_ITEM-MATNR',
            'NEW_ITEM-PRICEUNIT',
            'NEW_ITEM-LEADTIME',
            'NEW_ITEM-LONGTEXT',
            'NEW_ITEM-VENDOR',
            'NEW_ITEM-MANUFACTCODE',
            'NEW_ITEM-MANUFACTMAT',
            'NEW_ITEM-MATGROUP',
            'NEW_ITEM-SERVICE',
            'NEW_ITEM-CONTRACT',
            'NEW_ITEM-CONTRACT_ITEM',
            'NEW_ITEM-EXT_QUOTE_ID',
            'NEW_ITEM-EXT_QUOTE_ITEM',
            'NEW_ITEM-EXT_PRODUCT_ID',
            'NEW_ITEM-ATTACHMENT',
            'NEW_ITEM-ATTACHMENT_TITLE',
            'NEW_ITEM-ATTACHMENT_PURPOSE',
            'NEW_ITEM-EXT_SCHEMA_TYPE',
            'NEW_ITEM-EXT_CATEGORY_ID',
            'NEW_ITEM-EXT_CATEGORY',
            'NEW_ITEM-SLD_SYS_NAME',
            'NEW_ITEM-CUST_FIELD1',
            'NEW_ITEM-CUST_FIELD2',
            'NEW_ITEM-CUST_FIELD3',
            'NEW_ITEM-CUST_FIELD4',
            'NEW_ITEM-CUST_FIELD5',
            'NEW_ITEM-ITEM_TYPE',
            'NEW_ITEM-PARENT_ID',
        ];
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSupportedCxmlFields(): array
    {
        return [
            'attributes.language',
            'attributes.buyerCookie',
            'attributes.currency',
            'attributes.operationAllowed',
            'ItemID.SupplierPartID',
            'ItemID.SupplierPartAuxiliaryID',
            'ItemID.BuyerPartID',
            'attributes.quantity',
            'attributes.lineNumber',
            'attributes.requestedDeliveryDate',
            'attributes.agreementItemNumber',
            'attributes.parentAgreementID',
            'attributes.parentAgreementPayloadID',
            'attributes.isAdHoc',
            'ItemDetail.UnitPrice',
            'ItemDetail.Description',
            'ItemDetail.UnitOfMeasure',
            'ItemDetail.ManufacturerPartID',
            'ItemDetail.ManufacturerName',
            'ItemDetail.URL',
            'ItemDetail.LeadTime',
            'ItemDetail.ClassificationDomain',
            'ItemDetail.ClassificationValue',
            'Tax.Money',
            'Tax.Description',
            'Shipping.Money',
            'Shipping.Description',
            'ShipTo.Address.Name',
            'ShipTo.Address.PostalAddress.Street1',
            'ShipTo.Address.PostalAddress.Street2',
            'ShipTo.Address.PostalAddress.Street3',
            'ShipTo.Address.PostalAddress.City',
            'ShipTo.Address.PostalAddress.CountryCode',
            'ShipTo.Address.PostalAddress.State',
            'ShipTo.Address.PostalAddress.PostalCode',
            'cXML.attributes.xml:lang',
            'cXML.Header.From.Credential.domain',
            'cXML.Header.From.Credential.Identity',
            'cXML.Header.To.Credential.domain',
            'cXML.Header.To.Credential.Identity',
            'cXML.Header.Sender.Credential.domain',
            'cXML.Header.Sender.Credential.Identity',
            'cXML.Header.Sender.Credential.SharedSecret',
            'cXML.Header.Sender.UserAgent',
        ];
    }
}
