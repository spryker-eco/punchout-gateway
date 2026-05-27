<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;

interface PunchoutGatewayEntityManagerInterface
{
    public function createPunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer;

    public function updatePunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer;

    public function deletePunchoutConnection(int $idPunchoutConnection): bool;

    public function createPunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer;

    public function updatePunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer;

    public function deletePunchoutCredential(int $idPunchoutCredential): void;

    public function createPunchoutSession(PunchoutSessionTransfer $punchoutSessionTransfer): PunchoutSessionTransfer;

    public function deletePunchoutSessionByToken(PunchoutSessionTransfer $punchoutSessionTransfer): int;

    public function deletePunchoutSessionById(PunchoutSessionTransfer $punchoutSessionTransfer): int;

    public function deletePunchoutSessionByBuyerCookie(PunchoutSessionTransfer $punchoutSessionTransfer): int;
}
