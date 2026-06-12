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
    public function getExtrinsicDenyList(): array
    {
        return SharedPunchoutGatewayConfig::EXTRINSIC_DENY_LIST;
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
            'cXML.attr.xml:lang',
            'cXML.Header.From.Credential.attr.domain',
            'cXML.Header.From.Credential.Identity',
            'cXML.Header.To.Credential.attr.domain',
            'cXML.Header.To.Credential.Identity',
            'cXML.Header.Sender.Credential.attr.domain',
            'cXML.Header.Sender.Credential.Identity',
            'cXML.Header.Sender.Credential.SharedSecret',
            'cXML.Header.Sender.UserAgent',
            'cXML.Message.PunchOutOrderMessage.attr.xml:lang',
            'cXML.Message.PunchOutOrderMessage.BuyerCookie',
            'cXML.Message.PunchOutOrderMessage.attr.currency',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.attr.operationAllowed',
            'cXML.Message.PunchOutOrderMessage.ItemIn.attr.quantity',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemID.SupplierPartID',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemID.SupplierPartAuxiliaryID',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemID.BuyerPartID',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.UnitPrice.Money',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Description',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.UnitOfMeasure',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.ManufacturerPartID',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.ManufacturerName',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.URL',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.LeadTime',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Classification.attr.domain',
            'cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Classification',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Tax.Money',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Tax.Description',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Shipping.Money',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.Shipping.Description',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.attr.name',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Street1',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Street2',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Street3',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.City',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.Country.attr.isoCountryCode',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.State',
            'cXML.Message.PunchOutOrderMessage.PunchOutOrderMessageHeader.ShipTo.Address.PostalAddress.PostalCode',
        ];
    }
}
