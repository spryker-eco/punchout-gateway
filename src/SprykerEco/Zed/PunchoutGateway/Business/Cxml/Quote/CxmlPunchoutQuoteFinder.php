<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class CxmlPunchoutQuoteFinder implements CxmlPunchoutQuoteFinderInterface
{
    public function __construct(
        protected QuoteFacadeInterface $quoteFacade,
        protected PunchoutGatewayRepositoryInterface $repository,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function resolveQuote(PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer
    {
        $buyerCookie = $setupRequestTransfer->getCxmlSetupRequest()->getBuyerCookie();

        if ($buyerCookie === null || $buyerCookie === '') {
            $this->punchoutLogger->logGenericInfoMessage('BuyerCookie is empty, create new quote.');

            return $this->createDefaultQuote();
        }

        $existingQuote = $this->findQuoteByBuyerCookie($buyerCookie);

        if ($existingQuote === null) {
            $this->punchoutLogger->logGenericInfoMessage('Quote not found by BuyerCookie, create new quote.', ['BuyerCookie' => $buyerCookie]);

            return $this->createDefaultQuote();
        }

        return $existingQuote;
    }

    protected function createDefaultQuote(): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setName(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME);
    }

    protected function findQuoteByBuyerCookie(string $buyerCookie): ?QuoteTransfer
    {
        $punchoutSessionTransfer = $this->repository->findPunchoutSessionByBuyerCookie($buyerCookie);

        if ($punchoutSessionTransfer === null || $punchoutSessionTransfer->getIdQuote() === null) {
            return null;
        }

        $quoteResponseTransfer = $this->quoteFacade->findQuoteById($punchoutSessionTransfer->getIdQuote());

        if (!$quoteResponseTransfer->getIsSuccessful() || $quoteResponseTransfer->getQuoteTransfer() === null) {
            return null;
        }

        return $quoteResponseTransfer->getQuoteTransfer();
    }
}
