<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Session;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

interface SessionCreatorInterface
{
    public function createSession(
        PunchoutProcessorPluginInterface $processorPlugin,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?PunchoutSessionTransfer;
}
