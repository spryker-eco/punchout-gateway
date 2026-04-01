<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class CxmlPunchoutQuoteFinder implements CxmlPunchoutQuoteFinderInterface
{
    public function __construct(
        protected QuoteFacadeInterface $quoteFacade,
        protected PunchoutGatewayRepositoryInterface $repository,
    ) {
    }

    public function resolveQuote(PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer
    {
        $buyerCookie = $setupRequestTransfer->getCxmlSetupRequest()->getBuyerCookie();

        if ($buyerCookie === null || $buyerCookie === '') {
            return new QuoteTransfer();
        }

        $existingQuote = $this->findQuoteByBuyerCookie($buyerCookie);

        if ($existingQuote !== null) {
            return $existingQuote;
        }

        return new QuoteTransfer();
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
