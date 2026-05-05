<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Connection\Deleter;

use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;

class PunchoutConnectionDeleter implements PunchoutConnectionDeleterInterface
{
    public function __construct(protected PunchoutGatewayEntityManagerInterface $entityManager)
    {
    }

    public function deletePunchoutConnection(int $idPunchoutConnection): bool
    {
        return $this->entityManager->deletePunchoutConnection($idPunchoutConnection);
    }
}
