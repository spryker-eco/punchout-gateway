<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutSessionTransfer;

interface PunchoutGatewayEntityManagerInterface
{
    public function createPunchoutSession(PunchoutSessionTransfer $punchoutSessionTransfer): PunchoutSessionTransfer;

    public function deletePunchoutSessionByToken(PunchoutSessionTransfer $punchoutSessionTransfer): int;

    public function deletePunchoutSessionById(PunchoutSessionTransfer $punchoutSessionTransfer): int;

    public function deletePunchoutSessionByBuyerCookie(PunchoutSessionTransfer $punchoutSessionTransfer): int;
}
