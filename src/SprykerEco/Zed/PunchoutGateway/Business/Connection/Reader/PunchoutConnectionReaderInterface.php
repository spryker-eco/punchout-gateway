<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Connection\Reader;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;

interface PunchoutConnectionReaderInterface
{
    public function getPunchoutConnectionCollection(PunchoutConnectionCriteriaTransfer $criteriaTransfer): PunchoutConnectionCollectionTransfer;

    public function findPunchoutConnectionById(int $idPunchoutConnection): ?PunchoutConnectionTransfer;
}
