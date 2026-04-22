<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Client\PunchoutGateway\Zed;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

interface PunchoutGatewayStubInterface
{
    public function processPunchoutCxmlSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer;

    public function startPunchoutCxmlSession(
        PunchoutSessionStartRequestTransfer $sessionStartRequestTransfer,
    ): PunchoutSessionStartResponseTransfer;

    public function processPunchoutOciStartRequest(
        PunchoutOciLoginRequestTransfer $punchoutOciLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer;
}
