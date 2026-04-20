<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Client\PunchoutGateway;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

interface PunchoutGatewayClientInterface
{
    /**
     * Specification:
     * - Sends the PunchOut setup request to Zed for processing.
     * - Returns the PunchOut setup response with XML and status information.
     *
     * @api
     */
    public function processPunchoutCxmlSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer;

    /**
     * Specification:
     * - Validates the punchout session token via Zed.
     * - Returns customer and store data for session login.
     * - Returns isSuccess=false if token is invalid, expired, or connection inactive.
     *
     * @api
     */
    public function startPunchoutCxmlSession(
        PunchoutSessionStartRequestTransfer $sessionStartRequestTransfer,
    ): PunchoutSessionStartResponseTransfer;

    /**
     * Specification:
     * - Sends the OCI login request to Zed for processing.
     * - Returns the PunchOut setup response with redirect URL.
     *
     * @api
     */
    public function processPunchoutOciStartRequest(
        PunchoutOciLoginRequestTransfer $punchoutOciLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer;
}
