<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Quote;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class PunchoutQuoteExpander implements PunchoutQuoteExpanderInterface
{
    /**
     * @param array<\SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutSessionInQuoteExpanderPluginInterface> $expanderPlugins
     */
    public function __construct(
        protected PunchoutGatewayRepositoryInterface $repository,
        protected array $expanderPlugins,
    ) {
    }

    public function expand(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        $punchoutSessionTransfer = $this->repository->findPunchoutSessionByIdQuote(
            $quoteTransfer->getIdQuote(),
        );

        if ($punchoutSessionTransfer === null) {
            return $quoteTransfer;
        }

        $punchoutSessionTransfer = $this->expandSession($punchoutSessionTransfer, $quoteTransfer);

        $quoteTransfer->setPunchoutSession($punchoutSessionTransfer);

        return $quoteTransfer;
    }

    protected function expandSession(PunchoutSessionTransfer $punchoutSessionTransfer, QuoteTransfer $quoteTransfer): PunchoutSessionTransfer
    {
        foreach ($this->expanderPlugins as $expanderPlugin) {
            if (!$expanderPlugin->isApplicable($punchoutSessionTransfer, $quoteTransfer)) {
                continue;
            }

            $punchoutSessionTransfer = $expanderPlugin->expand($punchoutSessionTransfer, $quoteTransfer);
        }

        return $punchoutSessionTransfer;
    }
}
