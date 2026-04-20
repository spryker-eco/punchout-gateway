<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\FormBuilder;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;

class PunchoutFormDataBuilder implements PunchoutFormDataBuilderInterface
{
    /**
     * @param array<\SprykerEco\Yves\PunchoutGateway\Plugin\Form\PunchoutFormHandlerPluginInterface> $punchoutFormHandlerPlugins
     */
    public function __construct(
        protected array $punchoutFormHandlerPlugins,
        protected PunchoutLoggerInterface $punchoutLogger
    ) {
    }

    public function build(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer
    {
        foreach ($this->punchoutFormHandlerPlugins as $plugin) {
            if (!$plugin->isApplicable($quoteTransfer)) {
                continue;
            }

            $this->punchoutLogger->logGenericInfoMessage('Form builder is running.', ['class' => $plugin::class]);

            return $plugin->handle($quoteTransfer);
        }

        $this->punchoutLogger->logGenericErrorMessage('Form builder is failed to find a plugin for the quote.', ['quoteUuid' => $quoteTransfer->getUuid()]);

        return null;
    }
}
