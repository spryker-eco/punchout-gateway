<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
