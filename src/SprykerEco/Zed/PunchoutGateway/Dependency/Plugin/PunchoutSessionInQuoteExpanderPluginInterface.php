<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Dependency\Plugin;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

/**
 * Provides extension capabilities to extend active PunchOut session data.
 *
 * @api
 */
interface PunchoutSessionInQuoteExpanderPluginInterface
{
    /**
     * Specification:
     * - Checks if the plugin is applicable for the request.
     *
     * @api
     */
    public function isApplicable(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        QuoteTransfer $quoteTransfer,
    ): bool;

    /**
     * Specification:
     * - Expands the punchout session transfer before it is assigned to Quote.
     *
     * @api
     */
    public function expand(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer;
}
