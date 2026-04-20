<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
