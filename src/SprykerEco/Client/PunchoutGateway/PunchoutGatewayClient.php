<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Client\PunchoutGateway;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerEco\Client\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class PunchoutGatewayClient extends AbstractClient implements PunchoutGatewayClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function processPunchoutCxmlSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        return $this->getFactory()
            ->createPunchoutGatewayStub()
            ->processPunchoutCxmlSetupRequest($punchoutSetupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function processPunchoutOciStartRequest(
        PunchoutOciLoginRequestTransfer $punchoutOciLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        return $this->getFactory()
            ->createPunchoutGatewayStub()
            ->processPunchoutOciStartRequest($punchoutOciLoginRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function startPunchoutCxmlSession(
        PunchoutSessionStartRequestTransfer $sessionStartRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        return $this->getFactory()
            ->createPunchoutGatewayStub()
            ->startPunchoutCxmlSession($sessionStartRequestTransfer);
    }
}
