<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Dependency\Plugin;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

/**
 * Provides extension capabilities to handle PunchOut flow.
 *
 * @api
 */
interface PunchoutProcessorPluginInterface
{
    /**
     * Specification:
     * - Authenticates the login request against the given connection.
     * - Returns the authenticated connection transfer on success, or null on failure.
     *
     * @api
     */
    public function authenticate(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?PunchoutConnectionTransfer;

    /**
     * Specification:
     * - Resolves a Spryker customer from the request data.
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
     * - Expands the punchout session transfer with connection-specific fields before persistence.
     *
     * @api
     */
    public function resolveSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): ?PunchoutSessionTransfer;
}
