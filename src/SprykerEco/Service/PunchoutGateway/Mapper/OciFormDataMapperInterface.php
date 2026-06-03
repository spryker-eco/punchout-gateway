<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Mapper;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface OciFormDataMapperInterface
{
    /**
     * @api
     *
     * Builds the OCI form data transfer from the quote, applying per-connection field-map overrides.
     * Returns null when the quote has no punchout session, no browser form post URL, or no OCI login request.
     */
    public function mapOciFormData(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer;
}
