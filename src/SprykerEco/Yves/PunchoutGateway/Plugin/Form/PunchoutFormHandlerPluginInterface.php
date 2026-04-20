<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
