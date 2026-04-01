<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

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

        if (!$punchoutSessionTransfer) {
            return $quoteTransfer;
        }

        $punchoutSessionTransfer = $this->expandSession($punchoutSessionTransfer, $quoteTransfer);

        $quoteTransfer->setPunchoutSession($punchoutSessionTransfer);

        return $quoteTransfer;
    }

    protected function expandSession(PunchoutSessionTransfer $punchoutSessionTransfer, QuoteTransfer $quoteTransfer): PunchoutSessionTransfer
    {
        foreach ($this->expanderPlugins as $expanderPlugin) {
            if ($expanderPlugin->isApplicable($punchoutSessionTransfer, $quoteTransfer)) {
                $punchoutSessionTransfer = $expanderPlugin->expand($punchoutSessionTransfer, $quoteTransfer);
            }
        }

        return $punchoutSessionTransfer;
    }
}
