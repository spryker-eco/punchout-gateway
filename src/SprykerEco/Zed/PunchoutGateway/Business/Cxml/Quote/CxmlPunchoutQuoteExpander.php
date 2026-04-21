<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote;

use ArrayObject;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutAddressTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutItemTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class CxmlPunchoutQuoteExpander implements CxmlPunchoutQuoteExpanderInterface
{
    public function expand(QuoteTransfer $quoteTransfer, PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer
    {
        $cxmlRequest = $setupRequestTransfer->getCxmlSetupRequest();

        if ($cxmlRequest === null) {
            return $quoteTransfer;
        }

        $quoteTransfer = $this->mapShippingAddress($quoteTransfer, $cxmlRequest);

        if ($cxmlRequest->getOperation() === PunchoutGatewayConfig::OPERATION_EDIT) {
            $quoteTransfer = $this->mapItems($quoteTransfer, $cxmlRequest);
        }

        if ($cxmlRequest->getOperation() === PunchoutGatewayConfig::OPERATION_CREATE) {
            $quoteTransfer->setItems(new ArrayObject());
        }

        return $quoteTransfer;
    }

    protected function mapShippingAddress(
        QuoteTransfer $quoteTransfer,
        PunchoutCxmlSetupRequestTransfer $requestTransfer,
    ): QuoteTransfer {
        $punchoutAddressTransfer = $requestTransfer->getShipTo();

        if ($punchoutAddressTransfer === null) {
            return $quoteTransfer;
        }

        $addressTransfer = $this->mapPunchoutAddressToAddressTransfer($punchoutAddressTransfer);
        $quoteTransfer->setShippingAddress($addressTransfer);
        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            if ($itemTransfer->getShipment() === null) {
                $itemTransfer->setShipment(new ShipmentTransfer());
            }

            $itemTransfer->getShipment()->setShippingAddress($addressTransfer);
        }

        return $quoteTransfer;
    }

    protected function mapPunchoutAddressToAddressTransfer(
        PunchoutAddressTransfer $punchoutAddressTransfer,
    ): AddressTransfer {
        $addressTransfer = new AddressTransfer();

        $streetLines = $punchoutAddressTransfer->getStreet();
        if (count($streetLines) > 0) {
            $addressTransfer->setAddress1($streetLines[0]);
        }

        if (count($streetLines) > 1) {
            $addressTransfer->setAddress2($streetLines[1]);
        }

        if (count($streetLines) > 2) {
            $addressTransfer->setAddress3($streetLines[2]);
        }

        $addressTransfer->setCity($punchoutAddressTransfer->getCity());
        $addressTransfer->setState($punchoutAddressTransfer->getState());
        $addressTransfer->setZipCode($punchoutAddressTransfer->getPostalCode());
        $addressTransfer->setIso2Code($punchoutAddressTransfer->getCountryCode());

        return $addressTransfer;
    }

    protected function mapItems(
        QuoteTransfer $quoteTransfer,
        PunchoutCxmlSetupRequestTransfer $requestTransfer,
    ): QuoteTransfer {
        $quoteTransfer->setItems(new ArrayObject());

        foreach ($requestTransfer->getItems() as $punchoutItemTransfer) {
            $quoteTransfer->addItem(
                $this->mapPunchoutItemToItemTransfer($punchoutItemTransfer),
            );
        }

        return $quoteTransfer;
    }

    protected function mapPunchoutItemToItemTransfer(PunchoutItemTransfer $punchoutItemTransfer): ItemTransfer
    {
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($punchoutItemTransfer->getSupplierPartId());
        $itemTransfer->setQuantity($punchoutItemTransfer->getQuantity()->toInt());
        $itemTransfer->setUnitPrice($punchoutItemTransfer->getUnitPrice()->toInt());

        return $itemTransfer;
    }
}
