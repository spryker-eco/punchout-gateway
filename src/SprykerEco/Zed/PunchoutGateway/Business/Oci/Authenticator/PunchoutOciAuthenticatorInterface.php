<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;

interface PunchoutOciAuthenticatorInterface
{
    public function authenticateConnection(
        PunchoutSetupRequestTransfer $setupRequestTransfer
    ): ?PunchoutConnectionTransfer;
}
