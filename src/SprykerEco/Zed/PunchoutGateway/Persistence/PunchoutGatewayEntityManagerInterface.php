<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutSessionTransfer;

interface PunchoutGatewayEntityManagerInterface
{
    public function deletePunchoutSessionIfExists(PunchoutSessionTransfer $punchoutSessionTransfer): int;

    public function createPunchoutSession(PunchoutSessionTransfer $punchoutSessionTransfer): PunchoutSessionTransfer;
}
