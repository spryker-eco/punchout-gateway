<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Quote;

use Generated\Shared\Transfer\QuoteTransfer;

interface PunchoutQuoteExpanderInterface
{
    /**
     * Expands a quote with punchout session data if a session exists for the quote.
     */
    public function expand(QuoteTransfer $quoteTransfer): QuoteTransfer;
}
