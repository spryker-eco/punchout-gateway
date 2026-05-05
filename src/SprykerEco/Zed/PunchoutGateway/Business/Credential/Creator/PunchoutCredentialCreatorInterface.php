<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Credential\Creator;

use Generated\Shared\Transfer\PunchoutCredentialTransfer;

interface PunchoutCredentialCreatorInterface
{
    public function createPunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer;
}
