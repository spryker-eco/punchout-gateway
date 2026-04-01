<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Dependency\Plugin;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface PunchoutOciProcessorPluginInterface
{
    /**
     * Specification:
     * - Authenticates the OCI login request against the given connection.
     * - Uses connection's configured username/password field names to extract credentials from form data.
     * - Verifies credentials against the credential table.
     * - Returns the connection transfer enriched with credential data (e.g., idCustomer), or null on failure.
     *
     * @api
     */
    public function authenticate(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): ?PunchoutConnectionTransfer;

    /**
     * Specification:
     * - Resolves a Spryker customer from the OCI connection/credential data.
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
     *
     * @api
     */
    public function resolveQuote(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer;

    /**
     * Specification:
     * - Expands the quote with connection-specific data.
     *
     * @api
     */
    public function expandQuote(
        QuoteTransfer $quoteTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer;

    /**
     * Specification:
     * - Expands the punchout session transfer with OCI-specific fields.
     * - Sets operation, browser form post URL from hook_url form field, etc.
     *
     * @api
     */
    public function expandSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer;
}
