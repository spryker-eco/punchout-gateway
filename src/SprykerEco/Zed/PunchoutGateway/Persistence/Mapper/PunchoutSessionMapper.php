<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Persistence\Mapper;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;

class PunchoutSessionMapper
{
    public function mapPunchoutSessionEntityToTransfer(
        SpyPunchoutSession $punchoutSessionEntity,
        PunchoutSessionTransfer $punchoutSessionTransfer,
    ): PunchoutSessionTransfer {
        $punchoutSessionTransfer->fromArray($punchoutSessionEntity->toArray(), true);
        $punchoutSessionTransfer->setIdQuote($punchoutSessionEntity->getFkQuote());
        $punchoutSessionTransfer->setIdPunchoutConnection($punchoutSessionEntity->getFkPunchoutConnection());
        $punchoutSessionTransfer->setIdCustomer($punchoutSessionEntity->getFkCustomer());

        $extrinsics = $punchoutSessionEntity->getExtrinsics();

        if ($extrinsics !== null && $extrinsics !== '') {
            $decoded = json_decode($extrinsics, true);

            if (is_array($decoded)) {
                $punchoutSessionTransfer->setExtrinsics($decoded);
            }
        }

        $punchoutSessionTransfer->setConnection();

        return $punchoutSessionTransfer;
    }
}
