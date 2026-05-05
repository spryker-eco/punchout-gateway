<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Connection\Updater;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;

class PunchoutConnectionUpdater implements PunchoutConnectionUpdaterInterface
{
    public function __construct(protected PunchoutGatewayEntityManagerInterface $entityManager)
    {
    }

    public function updatePunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer
    {
        return $this->entityManager->updatePunchoutConnection($punchoutConnectionTransfer);
    }
}
