<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
