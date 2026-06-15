<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\FormBuilder;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface CxmlFormFieldBuilderInterface
{
    /**
     * Specification:
     * - Builds cXML form data from the quote transfer's punchout session.
     * - Returns null when the action URL or cXML payload is missing.
     *
     * @api
     */
    public function build(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer;
}
