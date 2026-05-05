<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Connection\Creator;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;

class PunchoutConnectionCreator implements PunchoutConnectionCreatorInterface
{
    public function __construct(protected PunchoutGatewayEntityManagerInterface $entityManager)
    {
    }

    public function createPunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer
    {
        return $this->entityManager->createPunchoutConnection($punchoutConnectionTransfer);
    }
}
