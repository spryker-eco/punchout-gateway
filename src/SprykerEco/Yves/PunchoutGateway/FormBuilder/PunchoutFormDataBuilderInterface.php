<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\FormBuilder;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface PunchoutFormDataBuilderInterface
{
    /**
     * Specification:
     * - Iterates registered form handler plugins and returns form data from the first applicable one.
     * - Returns null when no handler is applicable.
     *
     * @api
     */
    public function build(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer;
}
