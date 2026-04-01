<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\PunchoutGateway\Zed;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class PunchoutGatewayStub implements PunchoutGatewayStubInterface
{
    public function __construct(protected ZedRequestClientInterface $zedRequestClient)
    {
    }

    /**
     * @uses \SprykerEco\Zed\PunchoutGateway\Communication\Controller\GatewayController::processPunchoutCxmlSetupRequestAction
     */
    public function processPunchoutCxmlSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        /** @var \Generated\Shared\Transfer\PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer */
        $punchoutSetupResponseTransfer = $this->zedRequestClient->call(
            '/punchout-gateway/gateway/process-punchout-cxml-setup-request',
            $punchoutSetupRequestTransfer,
        );

        return $punchoutSetupResponseTransfer;
    }

    /**
     * @uses \SprykerEco\Zed\PunchoutGateway\Communication\Controller\GatewayController::processPunchoutOciStartRequestAction
     */
    public function processPunchoutOciStartRequest(
        PunchoutOciLoginRequestTransfer $punchoutOciLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        /** @var \Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer $punchoutSessionStartResponseTransfer */
        $punchoutSessionStartResponseTransfer = $this->zedRequestClient->call(
            '/punchout-gateway/gateway/process-punchout-oci-start-request',
            $punchoutOciLoginRequestTransfer,
        );

        return $punchoutSessionStartResponseTransfer;
    }

    /**
     * @uses \SprykerEco\Zed\PunchoutGateway\Communication\Controller\GatewayController::startPunchoutCxmlSessionAction
     */
    public function startPunchoutCxmlSession(
        PunchoutSessionStartRequestTransfer $sessionStartRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        /** @var \Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer */
        $sessionStartResponseTransfer = $this->zedRequestClient->call(
            '/punchout-gateway/gateway/start-punchout-cxml-session',
            $sessionStartRequestTransfer,
        );

        return $sessionStartResponseTransfer;
    }
}
