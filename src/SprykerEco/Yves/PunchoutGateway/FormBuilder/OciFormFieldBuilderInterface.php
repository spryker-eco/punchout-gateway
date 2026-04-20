<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\FormBuilder;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface OciFormFieldBuilderInterface
{
    /**
     * Specification:
     * - Builds OCI form fields from the quote transfer's items and punchout session data.
     * - Returns null when the required OCI session data is missing.
     *
     * @api
     */
    public function build(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer;
}
