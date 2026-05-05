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
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class PunchoutConnectionReader implements PunchoutConnectionReaderInterface
{
    public function __construct(protected PunchoutGatewayRepositoryInterface $repository)
    {
    }

    public function getPunchoutConnectionCollection(PunchoutConnectionCriteriaTransfer $criteriaTransfer): PunchoutConnectionCollectionTransfer
    {
        return $this->repository->getPunchoutConnectionCollection($criteriaTransfer);
    }

    public function findPunchoutConnectionById(int $idPunchoutConnection): ?PunchoutConnectionTransfer
    {
        return $this->repository->findPunchoutConnectionById($idPunchoutConnection);
    }
}
