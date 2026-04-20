<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
