<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class CxmlPunchoutSessionResolver implements CxmlPunchoutSessionResolverInterface
{
    protected const string TIMESTAMP_FORMAT = 'c';

    public function __construct(protected PunchoutGatewayConfig $config)
    {
    }

    public function resolve(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer {
        $cxmlRequest = $setupRequestTransfer->getCxmlSetupRequestOrFail();

        $punchoutSessionTransfer->setBuyerCookie($cxmlRequest->getBuyerCookie());
        $punchoutSessionTransfer->setBrowserFormPostUrl($cxmlRequest->getBrowserFormPostUrl());
        $punchoutSessionTransfer->setOperation($cxmlRequest->getOperation());
        $punchoutSessionTransfer->setValidTo(date(static::TIMESTAMP_FORMAT, time() + $this->config->getCxmlSessionStartUrlValidityInSeconds()));
        $punchoutSessionTransfer->setSessionToken(bin2hex(random_bytes(max(1, $this->config->getCxmlSessionTokenLength()))));
        $punchoutSessionTransfer->getPunchoutData()->setCxmlSetupRequest(clone $setupRequestTransfer->getCxmlSetupRequest());
        $punchoutSessionTransfer->getPunchoutData()->getCxmlSetupRequest()->setSenderSharedSecret();

        return $punchoutSessionTransfer;
    }
}
