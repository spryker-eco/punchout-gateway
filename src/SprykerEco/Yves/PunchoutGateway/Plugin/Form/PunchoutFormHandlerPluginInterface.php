<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Plugin\Form;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

/**
 * Provides protocol-specific form data for punchout cart submission.
 *
 * @api
 */
interface PunchoutFormHandlerPluginInterface
{
    /**
     * Specification:
     * - Checks whether this handler is applicable for the given quote's punchout session.
     * - Returns true when the quote carries punchout data matching this handler's protocol.
     *
     * @api
     */
    public function isApplicable(QuoteTransfer $quoteTransfer): bool;

    /**
     * Specification:
     * - Builds the form data transfer containing the action URL and all hidden form fields.
     * - Returns null when the form data cannot be built (e.g., missing session data).
     *
     * @api
     */
    public function handle(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer;
}
