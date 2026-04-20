<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Model;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

interface ProcessorPluginResolverInterface
{
    /**
     * @throws \SprykerEco\Zed\PunchoutGateway\Business\Exception\WrongProcessorException
     */
    public function resolveProcessorPlugin(
        PunchoutConnectionTransfer $connectionTransfer,
        string $expectedPluginInterface
    ): PunchoutProcessorPluginInterface;
}
