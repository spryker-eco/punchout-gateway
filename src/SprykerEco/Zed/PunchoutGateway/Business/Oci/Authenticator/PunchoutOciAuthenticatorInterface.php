<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;

interface PunchoutOciAuthenticatorInterface
{
    public function authenticate(PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer): ?PunchoutConnectionTransfer;

    public function authenticateConnection(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): ?PunchoutConnectionTransfer;
}
