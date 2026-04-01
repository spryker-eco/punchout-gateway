<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface CxmlPunchoutQuoteFinderInterface
{
    /**
     * Finds an existing quote by buyerCookie from a previous punchout session.
     * Returns a new QuoteTransfer if no existing quote is found.
     */
    public function resolveQuote(PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer;
}
