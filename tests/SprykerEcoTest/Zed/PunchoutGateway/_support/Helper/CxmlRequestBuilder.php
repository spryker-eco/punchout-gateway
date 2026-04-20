<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Helper;

use CXml\Model\Address;
use CXml\Model\Classification;
use CXml\Model\Country;
use CXml\Model\Credential;
use CXml\Model\CXml;
use CXml\Model\Description;
use CXml\Model\Header;
use CXml\Model\ItemDetail;
use CXml\Model\ItemId;
use CXml\Model\ItemOut;
use CXml\Model\MoneyWrapper;
use CXml\Model\MultilanguageString;
use CXml\Model\Party;
use CXml\Model\PayloadIdentity;
use CXml\Model\PostalAddress;
use CXml\Model\Request\PunchOutSetupRequest;
use CXml\Model\Request\Request;
use CXml\Model\ShipTo;
use CXml\Serializer;
use Generated\Shared\Transfer\PunchoutAddressTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutItemTransfer;

class CxmlRequestBuilder
{
    public static function buildSetupRequest(PunchoutCxmlSetupRequestTransfer $transfer): string
    {
        $punchoutSetupRequest = new PunchOutSetupRequest(
            buyerCookie: $transfer->getBuyerCookie(),
            browserFormPost: $transfer->getBrowserFormPostUrl(),
            supplierSetup: 'https://test.local/supplier-setup',
            shipTo: static::buildShipTo($transfer->getShipTo()),
            operation: $transfer->getOperation(),
        );

        foreach ($transfer->getExtrinsicFields() as $name => $value) {
            $punchoutSetupRequest->addExtrinsicAsKeyValue($name, (string)$value);
        }

        foreach ($transfer->getItems() as $punchoutItemTransfer) {
            $punchoutSetupRequest->addItem(static::buildItemOut($punchoutItemTransfer));
        }

        $header = new Header(
            from: new Party(new Credential('NetworkId', $transfer->getFromIdentity() ?? '')),
            to: new Party(new Credential('DUNS', $transfer->getToIdentity() ?? '')),
            sender: new Party(
                (new Credential('NetworkId', $transfer->getSenderIdentity() ?? ''))
                    ->setSharedSecret($transfer->getSenderSharedSecret()),
                'Test Agent',
            ),
        );

        $cxml = CXml::forRequest(
            new PayloadIdentity($transfer->getPayloadId() ?? sprintf('%s@test.local', uniqid())),
            new Request($punchoutSetupRequest, deploymentMode: CXml::DEPLOYMENT_PROD),
            $header,
        );

        return Serializer::create()->serialize($cxml);
    }

    protected static function buildItemOut(PunchoutItemTransfer $punchoutItemTransfer): ItemOut
    {
        $itemDetail = ItemDetail::create(
            new Description($punchoutItemTransfer->getDescription()),
            $punchoutItemTransfer->getUnitOfMeasure() ?? ItemDetail::UNIT_OF_MEASURE_EACH,
            new MoneyWrapper($punchoutItemTransfer->getCurrency() ?? 'EUR', $punchoutItemTransfer->getUnitPrice()->toInt()),
            [new Classification('unspsc', $punchoutItemTransfer->getClassification() ?? '00000000')],
        );

        return ItemOut::create(
            lineNumber: $punchoutItemTransfer->getLineNumber() ?? 1,
            quantity: (int)(float)(string)$punchoutItemTransfer->getQuantity(),
            itemId: new ItemId($punchoutItemTransfer->getSupplierPartId() ?? ''),
            itemDetail: $itemDetail,
        );
    }

    protected static function buildShipTo(?PunchoutAddressTransfer $addressTransfer): ?ShipTo
    {
        if ($addressTransfer === null) {
            return null;
        }

        $postalAddress = new PostalAddress(
            deliverTo: [],
            street: $addressTransfer->getStreet(),
            city: $addressTransfer->getCity() ?? '',
            country: new Country(
                isoCountryCode: $addressTransfer->getCountryCode() ?? '',
                name: $addressTransfer->getCountry(),
            ),
            state: $addressTransfer->getState(),
            postalCode: $addressTransfer->getPostalCode(),
        );

        return new ShipTo(
            new Address(
                new MultilanguageString($addressTransfer->getAddressName()),
                $postalAddress,
            ),
        );
    }
}
