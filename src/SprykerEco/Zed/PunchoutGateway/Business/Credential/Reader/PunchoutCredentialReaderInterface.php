<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Credential\Reader;

use Generated\Shared\Transfer\PunchoutCredentialCollectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;

interface PunchoutCredentialReaderInterface
{
    public function getPunchoutCredentialCollection(PunchoutCredentialCriteriaTransfer $criteriaTransfer): PunchoutCredentialCollectionTransfer;

    public function findPunchoutCredentialById(int $idPunchoutCredential): ?PunchoutCredentialTransfer;
}
