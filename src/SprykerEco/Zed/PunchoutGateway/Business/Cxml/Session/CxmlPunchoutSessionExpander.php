<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class CxmlPunchoutSessionExpander implements CxmlPunchoutSessionExpanderInterface
{
    public function __construct(protected PunchoutGatewayConfig $config)
    {
    }

    public function expand(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer {
        $cxmlRequest = $setupRequestTransfer->getCxmlSetupRequestOrFail();

        $punchoutSessionTransfer->setBuyerCookie($cxmlRequest->getBuyerCookie());
        $punchoutSessionTransfer->setBrowserFormPostUrl($cxmlRequest->getBrowserFormPostUrl());
        $punchoutSessionTransfer->setOperation($cxmlRequest->getOperation());
        $punchoutSessionTransfer->setValidTo(date('c', time() + $this->config->getCxmlSessionStartUrlSeconds()));

        $punchoutSessionTransfer->setSessionToken(uniqid());

        return $punchoutSessionTransfer;
    }
}
