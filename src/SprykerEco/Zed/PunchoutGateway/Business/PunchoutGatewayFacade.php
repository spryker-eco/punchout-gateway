<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface getEntityManager()
 */
class PunchoutGatewayFacade extends AbstractFacade implements PunchoutGatewayFacadeInterface
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
            ->createPunchoutCxmlSetupRequestProcessor()
            ->processSetupRequest($punchoutSetupRequestTransfer);
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
            ->createPunchoutCxmlSessionStarter()
            ->startSession($sessionStartRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function processPunchoutOciLoginRequest(
        PunchoutOciLoginRequestTransfer $punchoutOciLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        return $this->getFactory()
            ->createPunchoutOciLoginProcessor()
            ->processLoginRequest($punchoutOciLoginRequestTransfer);
    }
}
