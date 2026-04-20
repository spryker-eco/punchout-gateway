<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
