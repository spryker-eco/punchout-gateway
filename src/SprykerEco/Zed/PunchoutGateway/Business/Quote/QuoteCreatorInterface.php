<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

interface QuoteCreatorInterface
{
    public function createQuote(
        PunchoutProcessorPluginInterface $processorPlugin,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): PunchoutSetupRequestTransfer;
}
