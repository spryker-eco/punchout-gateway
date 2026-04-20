<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class OciPunchoutQuoteFinder implements OciPunchoutQuoteFinderInterface
{
    public function resolveQuote(PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setName(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME);
    }
}
