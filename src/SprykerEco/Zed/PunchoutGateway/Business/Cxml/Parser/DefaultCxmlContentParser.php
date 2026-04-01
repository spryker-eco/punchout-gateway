<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Parser;

use CXml\Model\CXml;
use CXml\Model\Request\PunchOutSetupRequest;
use Generated\Shared\Transfer\PunchoutAddressTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutItemTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

class DefaultCxmlContentParser implements DefaultCxmlContentParserInterface
{
    public function parseCxmlData(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
        CXml $cxml
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
        $punchoutSetupRequestTransfer->setTimestamp($cxml->timestamp->format('c'));

        $request = $cxml->request;

        if ($request === null || !($request->payload instanceof PunchOutSetupRequest)) {
            return $punchoutSetupRequestTransfer;
        }

        $punchoutSetupRequest = $request->payload;

        $punchoutSetupRequestTransfer->setOperation($punchoutSetupRequest->operation);
        $punchoutSetupRequestTransfer->setBuyerCookie($punchoutSetupRequest->buyerCookie);
        $punchoutSetupRequestTransfer->setBrowserFormPostUrl($punchoutSetupRequest->browserFormPost->url);
        $punchoutSetupRequestTransfer->setExtrinsics($punchoutSetupRequest->getExtrinsicsAsKeyValue());

        $this->mapShipTo($punchoutSetupRequest, $punchoutSetupRequestTransfer);

        if ($punchoutSetupRequest->operation === PunchoutGatewayConstants::OPERATION_EDIT) {
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
                $itemTransfer->setDescription((string)($itemOut->itemDetail->description->value ?? null));
                $itemTransfer->setUnitOfMeasure($itemOut->itemDetail->unitOfMeasure?->value ?? null);

                if ($itemOut->itemDetail->unitPrice !== null) {
                    $itemTransfer->setUnitPrice($itemOut->itemDetail->unitPrice->money->value);
                    $itemTransfer->setCurrency($itemOut->itemDetail->unitPrice->money->currency);
                }

                $classifications = $itemOut->itemDetail->getClassifications();

                if ($classifications !== []) {
                    $itemTransfer->setClassification($classifications[0]->value ?? null);
                }

                $itemTransfer->setManufacturerPartId($itemOut->itemDetail->getManufacturerPartId());
                $itemTransfer->setManufacturerName($itemOut->itemDetail->getManufacturerName());
            }

            $punchoutSetupRequestTransfer->addItem($itemTransfer);
        }
    }
}
