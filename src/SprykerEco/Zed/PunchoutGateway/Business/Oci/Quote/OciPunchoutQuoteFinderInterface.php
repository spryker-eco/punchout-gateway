<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface OciPunchoutQuoteFinderInterface
{
    public function resolveQuote(PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer;
}
