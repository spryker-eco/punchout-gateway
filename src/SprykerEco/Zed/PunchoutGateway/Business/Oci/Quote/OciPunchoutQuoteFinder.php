<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
