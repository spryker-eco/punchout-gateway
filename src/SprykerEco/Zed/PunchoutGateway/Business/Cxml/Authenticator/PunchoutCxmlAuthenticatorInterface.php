<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;

interface PunchoutCxmlAuthenticatorInterface
{
    public function authenticate(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): ?PunchoutConnectionTransfer;
}
