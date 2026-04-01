<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;

interface PunchoutGatewayRepositoryInterface
{
    /**
     * Retrieves a collection of punchout connections filtered by the given criteria conditions.
     */
    public function getPunchoutConnectionCollection(
        PunchoutConnectionCriteriaTransfer $punchoutConnectionCriteriaTransfer,
    ): PunchoutConnectionCollectionTransfer;

    /**
     * Finds an active cXML punchout connection by sender identity.
     * Returns null if no active connection exists for the given identity.
     */
    public function findActiveCxmlConnectionBySenderIdentity(string $senderIdentity): ?PunchoutConnectionTransfer;

    /**
     * Finds a punchout session by the associated quote ID.
     * Returns null if no session exists for the given quote.
     */
    public function findPunchoutSessionByIdQuote(int $idQuote): ?PunchoutSessionTransfer;

    /**
     * Finds a punchout session by buyer cookie value.
     * Returns null if no session exists for the given cookie.
     */
    public function findPunchoutSessionByBuyerCookie(string $buyerCookie): ?PunchoutSessionTransfer;

    /**
     * Finds an active punchout credential by username.
     * Returns null if no active credential exists.
     */
    public function findActiveCredentialByUsername(string $username): ?PunchoutCredentialTransfer;

    /**
     * Finds an active OCI punchout connection by request URL.
     * Returns null if no active OCI connection exists for the given URL.
     */
    public function findActiveOciConnectionByRequestUrl(string $requestUrl): ?PunchoutConnectionTransfer;

    /**
     * Finds a punchout session by token, validating in a single query:
     * - Session token matches
     * - Session has not expired (valid_to > now)
     * - Associated connection is active
     * Returns null if any condition fails.
     */
    public function findValidPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer;

    /**
     * Finds a punchout session by token:
     * - Session token matches
     */
    public function findPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer;
}
