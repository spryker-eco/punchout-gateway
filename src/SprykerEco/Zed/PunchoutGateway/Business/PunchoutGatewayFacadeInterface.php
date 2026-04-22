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

interface PunchoutGatewayFacadeInterface
{
    /**
     * Specification:
     * - Parses the raw cXML from the setup request transfer.
     * - Authenticates the sender against stored punchout connections.
     * - Resolves customer via customer resolver plugins.
     * - Builds quote with cart data from the setup request.
     * - Persists punchout session linked to the quote.
     * - Builds and returns the cXML response.
     * - Returns an error response if authentication or customer resolution fails.
     *
     * @api
     */
    public function processPunchoutCxmlSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer;

    /**
     * Specification:
     * - Authenticates the OCI login request via username/password credentials.
     * - Resolves customer via customer resolver plugins.
     * - Builds quote for the punchout session.
     * - Persists punchout session linked to the quote.
     * - Returns a response with startPageUrl for redirect.
     * - Returns an error response if authentication or customer resolution fails.
     *
     * @api
     */
    public function processPunchoutOciLoginRequest(
        PunchoutOciLoginRequestTransfer $punchoutOciLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer;

    /**
     * Specification:
     * - Finds a punchout session by session token.
     * - Validates the session has not expired (valid_to > now).
     * - Validates the associated connection is active.
     * - Resolves the customer by ID from the session.
     * - Resolves the store name from the connection.
     * - Returns isSuccess=false with errorMessage if any validation fails.
     *
     * @api
     */
    public function startPunchoutCxmlSession(
        PunchoutSessionStartRequestTransfer $sessionStartRequestTransfer,
    ): PunchoutSessionStartResponseTransfer;
}
