<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Response;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

interface CxmlPunchoutResponseExpanderInterface
{
    public function expand(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupResponseTransfer $responseTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer;
}
