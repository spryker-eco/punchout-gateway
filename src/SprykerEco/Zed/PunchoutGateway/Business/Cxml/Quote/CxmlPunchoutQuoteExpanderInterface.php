<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface CxmlPunchoutQuoteExpanderInterface
{
    /**
     * Expands quote with cXML-specific data: shipping address and items for edit operations.
     */
    public function expand(QuoteTransfer $quoteTransfer, PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer;
}
