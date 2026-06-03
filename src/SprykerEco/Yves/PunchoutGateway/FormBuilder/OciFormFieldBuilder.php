<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\FormBuilder;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;

class OciFormFieldBuilder implements OciFormFieldBuilderInterface
{
    public function __construct(protected PunchoutGatewayServiceInterface $punchoutGatewayService)
    {
    }

    public function build(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer
    {
        return $this->punchoutGatewayService->mapOciFormData($quoteTransfer);
    }
}
