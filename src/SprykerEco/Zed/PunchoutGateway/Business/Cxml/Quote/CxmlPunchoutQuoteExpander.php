<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote;

use ArrayObject;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutAddressTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutItemTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

class CxmlPunchoutQuoteExpander implements CxmlPunchoutQuoteExpanderInterface
{
    public function expand(QuoteTransfer $quoteTransfer, PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer
    {
        $cxmlRequest = $setupRequestTransfer->getCxmlSetupRequest();

        if ($cxmlRequest === null) {
            return $quoteTransfer;
        }

        $quoteTransfer = $this->mapShippingAddress($quoteTransfer, $cxmlRequest);

        if ($cxmlRequest->getOperation() === PunchoutGatewayConstants::OPERATION_EDIT) {
            $quoteTransfer = $this->mapItems($quoteTransfer, $cxmlRequest);
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
        $itemTransfer->setQuantity($punchoutItemTransfer->getQuantity());
        $itemTransfer->setUnitPrice((int)$punchoutItemTransfer->getUnitPrice());

        return $itemTransfer;
    }
}
