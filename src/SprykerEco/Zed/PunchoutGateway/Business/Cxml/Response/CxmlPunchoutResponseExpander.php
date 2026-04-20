<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Response;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class CxmlPunchoutResponseExpander implements CxmlPunchoutResponseExpanderInterface
{
    public function expand(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupResponseTransfer $responseTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        $responseTransfer->setPayloadId($punchoutCxmlSetupRequestTransfer->getPayloadId());
        $responseTransfer->setTimestamp($punchoutCxmlSetupRequestTransfer->getTimestamp());
        $responseTransfer->setStartPageUrl(
            sprintf(PunchoutGatewayConfig::CXML_SESSION_START_URL, $punchoutSessionTransfer->getSessionToken()),
        );

        return $responseTransfer;
    }
}
