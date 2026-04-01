<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Dependency\Plugin;

use CXml\Model\CXml;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface PunchoutCxmlProcessorPluginInterface
{
    /**
     * Specification:
     * - Parses the deserialized cXML object into a structured transfer.
     * - Extracts sender identity, shared secret, buyer cookie, items,
     *   contact, shipping address, extrinsics, and other protocol fields.
     *
     * @api
     */
    public function parseCxmlRequest(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        CXml $cxml,
    ): PunchoutCxmlSetupRequestTransfer;

    /**
     * Specification:
     * - Verifies credentials from the parsed cXML request against the connection config.
     * - Returns the authenticated connection transfer on success, or null on failure.
     *
     * @api
     */
    public function authenticate(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): ?PunchoutConnectionTransfer;

    /**
     * Specification:
     * - Resolves a Spryker customer from the parsed cXML request data.
     * - Returns null if the customer cannot be resolved.
     *
     * @api
     */
    public function resolveCustomer(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?CustomerTransfer;

    /**
     * Specification:
     * - Finds an existing quote for this punchout session or returns an empty QuoteTransfer.
     * - Used to resume an edit session when a matching quote exists.
     *
     * @api
     */
    public function resolveQuote(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer;

    /**
     * Specification:
     * - Expands the quote with connection-specific data (shipping address, items, etc.).
     *
     * @api
     */
    public function expandQuote(
        QuoteTransfer $quoteTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer;

    /**
     * Specification:
     * - Expands the punchout session transfer with connection-specific fields before persistence.
     * - Sets buyer cookie, browser form post URL, operation, validity, session token, etc.
     *
     * @api
     */
    public function expandSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer;

    /**
     * Specification:
     * - Expand the response transfer with needed data, including redirect URL.
     *
     * @api
     */
    public function expandResponse(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupResponseTransfer $responseTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer
    ): PunchoutSetupResponseTransfer;
}
