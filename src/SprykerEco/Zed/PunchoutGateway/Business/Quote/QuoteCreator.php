<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

class QuoteCreator implements QuoteCreatorInterface
{
    public function __construct(
        protected QuoteFacadeInterface $quoteFacade,
        protected StoreFacadeInterface $storeFacade,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function createQuote(
        PunchoutProcessorPluginInterface $processorPlugin,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): PunchoutSetupRequestTransfer {
        $quoteTransfer = $processorPlugin->resolveQuote($setupRequestTransfer);

        $storeTransfer = $this->storeFacade->getStoreById($setupRequestTransfer->getConnection()->getIdStore());
        $quoteTransfer->setCustomer($setupRequestTransfer->getCustomer());
        $quoteTransfer->setStore($storeTransfer);

        $quoteTransfer = $processorPlugin->expandQuote($quoteTransfer, $setupRequestTransfer);
        $quoteTransfer = $this->saveQuote($quoteTransfer);

        $setupRequestTransfer->setQuote($quoteTransfer);

        return $setupRequestTransfer;
    }

    protected function saveQuote(QuoteTransfer $quoteTransfer): ?QuoteTransfer
    {
        if ($quoteTransfer->getIdQuote() !== null) {
            return $this->quoteFacade->updateQuote($quoteTransfer)->getQuoteTransfer();
        }

        $quoteResponseTransfer = $this->quoteFacade->createQuote($quoteTransfer);

        if (!$quoteResponseTransfer->getIsSuccessful()) {
            $this->punchoutLogger->logQuoteCreationFailed($quoteResponseTransfer);

            return null;
        }

        $this->punchoutLogger->logQuoteCreated($quoteTransfer);

        return $quoteResponseTransfer->getQuoteTransfer();
    }
}
