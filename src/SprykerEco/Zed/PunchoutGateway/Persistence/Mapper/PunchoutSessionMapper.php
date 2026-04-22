<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence\Mapper;

use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;

class PunchoutSessionMapper
{
    public function __construct(protected UtilEncodingServiceInterface $utilEncodingService)
    {
    }

    public function mapPunchoutSessionEntityToTransfer(
        SpyPunchoutSession $punchoutSessionEntity,
        PunchoutSessionTransfer $punchoutSessionTransfer,
    ): PunchoutSessionTransfer {
        $punchoutSessionTransfer->fromArray($punchoutSessionEntity->toArray(), true);
        $punchoutSessionTransfer->setIdQuote($punchoutSessionEntity->getFkQuote());
        $punchoutSessionTransfer->setIdPunchoutConnection($punchoutSessionEntity->getFkPunchoutConnection());
        $punchoutSessionTransfer->setIdCustomer($punchoutSessionEntity->getFkCustomer());

        $sessionDataTransfer = new PunchoutSessionDataTransfer();
        $punchoutSessionTransfer->setPunchoutData($sessionDataTransfer);

        $sessionData = $punchoutSessionEntity->getSessionData();
        if ($sessionData) {
            $decodedData = $this->utilEncodingService->decodeJson($sessionData, true);

            if ($decodedData) {
                $sessionDataTransfer->fromArray($decodedData, true);
            }
        }

        return $punchoutSessionTransfer;
    }
}
