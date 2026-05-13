<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Quote;

use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\TaxTotalTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;
use Spryker\Zed\Currency\Business\CurrencyFacadeInterface;
use Spryker\Zed\Price\Business\PriceFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

class QuoteCreator implements QuoteCreatorInterface
{
    public function __construct(
        protected QuoteFacadeInterface $quoteFacade,
        protected StoreFacadeInterface $storeFacade,
        protected CurrencyFacadeInterface $currencyFacade,
        protected CalculationFacadeInterface $calculationFacade,
        protected PriceFacadeInterface $priceFacade,
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
        $quoteTransfer->setCurrency($this->currencyFacade->fromIsoCode($storeTransfer->getDefaultCurrencyIsoCode()));
        $quoteTransfer->setPriceMode($this->priceFacade->getDefaultPriceMode());

        $quoteTransfer = $processorPlugin->expandQuote($quoteTransfer, $setupRequestTransfer);

        $quoteTransfer = $this->recalculateQuote($quoteTransfer);

        $quoteTransfer = $this->saveQuote($quoteTransfer);

        $setupRequestTransfer->setQuote($quoteTransfer);

        return $setupRequestTransfer;
    }

    protected function recalculateQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        if ($quoteTransfer->getItems()->count() > 0) {
            return $this->calculationFacade->recalculateQuote($quoteTransfer);
        }

        return $quoteTransfer->setTotals(
            (new TotalsTransfer())
                ->setSubtotal(0)
                ->setExpenseTotal(0)
                ->setDiscountTotal(0)
                ->setGrandTotal(0)
                ->setNetTotal(0)
                ->setCanceledTotal(0)
                ->setRefundTotal(0)
                ->setTaxTotal((new TaxTotalTransfer())->setAmount(0)),
        );
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
