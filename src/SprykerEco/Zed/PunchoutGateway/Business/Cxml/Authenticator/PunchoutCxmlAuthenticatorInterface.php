<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;

interface PunchoutCxmlAuthenticatorInterface
{
    public function authenticateConnection(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?PunchoutConnectionTransfer;
}
