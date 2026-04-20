<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Model;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use SprykerEco\Zed\PunchoutGateway\Business\Exception\WrongProcessorException;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

class ProcessorPluginResolver implements ProcessorPluginResolverInterface
{
    /**
     * @throws \SprykerEco\Zed\PunchoutGateway\Business\Exception\WrongProcessorException
     */
    public function resolveProcessorPlugin(
        PunchoutConnectionTransfer $connectionTransfer,
        string $expectedPluginInterface
    ): PunchoutProcessorPluginInterface {
        $pluginClassName = $connectionTransfer->getProcessorPluginClassOrFail();

        if (!class_exists($pluginClassName)) {
            throw new WrongProcessorException(sprintf(
                'Processor %s for the connection %s #%s does not exist.',
                $pluginClassName,
                $connectionTransfer->getName(),
                $connectionTransfer->getIdPunchoutConnection(),
            ));
        }

        if (!is_subclass_of($pluginClassName, $expectedPluginInterface)) {
            throw new WrongProcessorException(sprintf(
                'Processor %s for the connection %s #%s is not of a valid interface %s.',
                $pluginClassName,
                $connectionTransfer->getName(),
                $connectionTransfer->getIdPunchoutConnection(),
                $expectedPluginInterface,
            ));
        }

        return new $pluginClassName();
    }
}
