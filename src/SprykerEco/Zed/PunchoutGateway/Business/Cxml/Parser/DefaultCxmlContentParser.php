<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Parser;

use CXml\Model\CXml;
use CXml\Model\ItemOut;
use CXml\Model\Request\PunchOutSetupRequest;
use Generated\Shared\Transfer\PunchoutAddressTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutItemTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class DefaultCxmlContentParser implements DefaultCxmlContentParserInterface
{
    protected const string TIMESTAMP_FORMAT = 'c';

    public function parseCxmlData(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
        CXml $cxml,
    ): PunchoutCxmlSetupRequestTransfer {
        $header = $cxml->header;

        if ($header !== null) {
            $punchoutSetupRequestTransfer->setSenderIdentity($header->sender->credential->identity);
            $punchoutSetupRequestTransfer->setSenderSharedSecret($header->sender->credential->getSharedSecret());
            $punchoutSetupRequestTransfer->setFromIdentity($header->from->credential->identity);
            $punchoutSetupRequestTransfer->setBuyerIdentity($header->from->credential->identity);
            $punchoutSetupRequestTransfer->setToIdentity($header->to->credential->identity);
        }

        $punchoutSetupRequestTransfer->setPayloadId($cxml->payloadId);
        $punchoutSetupRequestTransfer->setTimestamp($cxml->timestamp->format(static::TIMESTAMP_FORMAT));

        $request = $cxml->request;

        if ($request === null || !($request->payload instanceof PunchOutSetupRequest)) {
            return $punchoutSetupRequestTransfer;
        }

        $punchoutSetupRequest = $request->payload;

        $punchoutSetupRequestTransfer->setOperation($punchoutSetupRequest->operation);
        $punchoutSetupRequestTransfer->setBuyerCookie($punchoutSetupRequest->buyerCookie);
        $punchoutSetupRequestTransfer->setBrowserFormPostUrl($punchoutSetupRequest->browserFormPost->url);
        $punchoutSetupRequestTransfer->setExtrinsicFields($punchoutSetupRequest->getExtrinsicsAsKeyValue());

        $this->mapShipTo($punchoutSetupRequest, $punchoutSetupRequestTransfer);

        if ($punchoutSetupRequest->operation === PunchoutGatewayConfig::OPERATION_EDIT) {
            $this->mapItems($punchoutSetupRequest, $punchoutSetupRequestTransfer);
        }

        return $punchoutSetupRequestTransfer;
    }

    protected function mapShipTo(
        PunchOutSetupRequest $punchoutSetupRequest,
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): void {
        if ($punchoutSetupRequest->shipTo === null) {
            return;
        }

        $address = $punchoutSetupRequest->shipTo->address;
        $addressTransfer = new PunchoutAddressTransfer();
        $addressTransfer->setAddressName((string)$address->name->value);

        if ($address->postalAddress !== null) {
            $postalAddress = $address->postalAddress;

            foreach ($postalAddress->street as $streetLine) {
                $addressTransfer->addStreetLine($streetLine);
            }

            $addressTransfer->setCity($postalAddress->city);
            $addressTransfer->setState($postalAddress->state);
            $addressTransfer->setPostalCode($postalAddress->postalCode);
            $addressTransfer->setCountry($postalAddress->country->name ?? null);
            $addressTransfer->setCountryCode($postalAddress->country->isoCountryCode ?? null);
        }

        $punchoutSetupRequestTransfer->setShipTo($addressTransfer);
    }

    protected function mapItems(
        PunchOutSetupRequest $punchoutSetupRequest,
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): void {
        foreach ($punchoutSetupRequest->getItems() as $itemOut) {
            $itemTransfer = new PunchoutItemTransfer();
            $itemTransfer->setLineNumber($itemOut->lineNumber);
            $itemTransfer->setQuantity((string)$itemOut->quantity);
            $itemTransfer->setSupplierPartId($itemOut->itemId->supplierPartId);
            $itemTransfer->setSupplierPartAuxiliaryId($itemOut->itemId->supplierPartAuxiliaryId ?? null);

            if ($itemOut->itemDetail !== null) {
                $itemTransfer = $this->mapItemDetails($itemTransfer, $itemOut);
            }

            $punchoutSetupRequestTransfer->addItem($itemTransfer);
        }
    }

    protected function mapItemDetails(PunchoutItemTransfer $itemTransfer, ItemOut $itemOut): PunchoutItemTransfer
    {
        $itemTransfer->setDescription((string)($itemOut->itemDetail->description->value ?? null));
        $itemTransfer->setUnitOfMeasure($itemOut->itemDetail->unitOfMeasure ?? null);

        $itemTransfer->setUnitPrice($itemOut->itemDetail->unitPrice->money->value);
        $itemTransfer->setCurrency($itemOut->itemDetail->unitPrice->money->currency);

        $classifications = $itemOut->itemDetail->getClassifications();

        if ($classifications !== []) {
            $itemTransfer->setClassification($classifications[0]->value ?? null);
        }

        $itemTransfer->setManufacturerPartId($itemOut->itemDetail->getManufacturerPartId());
        $itemTransfer->setManufacturerName($itemOut->itemDetail->getManufacturerName());

        return $itemTransfer;
    }
}
