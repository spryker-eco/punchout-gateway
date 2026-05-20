<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCollectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

interface PunchoutGatewayFacadeInterface
{
    /**
     * Specification:
     * - Creates a new punchout connection and persists it to the database.
     * - Encodes protocol-specific configuration (cXML/OCI) into the configuration column.
     * - Returns the transfer with the generated ID.
     *
     * @api
     */
    public function createPunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer;

    /**
     * Specification:
     * - Updates an existing punchout connection identified by idPunchoutConnection.
     * - Encodes protocol-specific configuration (cXML/OCI) into the configuration column.
     * - Returns the updated transfer.
     *
     * @api
     */
    public function updatePunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer;

    /**
     * Specification:
     * - Deletes a punchout connection by ID.
     * - Cascade FK deletes associated credentials and sessions.
     *
     * @api
     */
    public function deletePunchoutConnection(int $idPunchoutConnection): bool;

    /**
     * Specification:
     * - Finds a punchout connection by its primary key.
     * - Returns null if not found.
     *
     * @api
     */
    public function findPunchoutConnectionById(int $idPunchoutConnection): ?PunchoutConnectionTransfer;

    /**
     * Specification:
     * - Returns a collection of punchout connections filtered by the given criteria.
     * - Supports filtering by store, protocol type, active status, and search term.
     * - Supports pagination via the criteria pagination transfer.
     *
     * @api
     */
    public function getPunchoutConnectionCollection(PunchoutConnectionCriteriaTransfer $criteriaTransfer): PunchoutConnectionCollectionTransfer;

    /**
     * Specification:
     * - Creates a new punchout credential for the given connection.
     * - Password must already be hashed by the caller.
     * - Returns the transfer with the generated ID.
     *
     * @api
     */
    public function createPunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer;

    /**
     * Specification:
     * - Updates an existing punchout credential identified by idPunchoutCredential.
     * - Password hash is updated only when provided (non-null passwordHash).
     * - Returns the updated transfer.
     *
     * @api
     */
    public function updatePunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer;

    /**
     * Specification:
     * - Deletes a punchout credential by ID.
     *
     * @api
     */
    public function deletePunchoutCredential(int $idPunchoutCredential): void;

    /**
     * Specification:
     * - Finds a punchout credential by its primary key.
     * - Returns null if not found.
     *
     * @api
     */
    public function findPunchoutCredentialById(int $idPunchoutCredential): ?PunchoutCredentialTransfer;

    /**
     * Specification:
     * - Returns a collection of punchout credentials for the given connection.
     *
     * @api
     */
    public function getPunchoutCredentialCollection(PunchoutCredentialCriteriaTransfer $criteriaTransfer): PunchoutCredentialCollectionTransfer;

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
