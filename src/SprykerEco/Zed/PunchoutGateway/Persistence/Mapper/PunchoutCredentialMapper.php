<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence\Mapper;

use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutCredential;

class PunchoutCredentialMapper
{
    public function mapCredentialEntityToTransfer(
        SpyPunchoutCredential $credentialEntity,
        PunchoutCredentialTransfer $credentialTransfer,
    ): PunchoutCredentialTransfer {
        $credentialTransfer->fromArray($credentialEntity->toArray(), true);

        $credentialTransfer->setIdPunchoutConnection($credentialEntity->getFkPunchoutConnection());
        $credentialTransfer->setIdCustomer($credentialEntity->getFkCustomer());

        return $credentialTransfer;
    }
}
