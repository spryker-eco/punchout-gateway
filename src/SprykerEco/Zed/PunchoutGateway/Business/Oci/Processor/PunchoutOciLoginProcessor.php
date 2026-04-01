<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Processor;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutOciProcessorPluginInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Throwable;

class PunchoutOciLoginProcessor implements PunchoutOciLoginProcessorInterface
{
    public function __construct(
        protected QuoteFacadeInterface $quoteFacade,
        protected StoreFacadeInterface $storeFacade,
        protected PunchoutGatewayConfig $config,
        protected PunchoutGatewayEntityManagerInterface $entityManager,
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayRepositoryInterface $repository,
    ) {
    }

    public function processLoginRequest(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        try {
            return $this->executeProcessLoginRequest($ociLoginRequestTransfer);
        } catch (Throwable $throwable) {
            $this->punchoutLogger->logError('PunchOut OCI login processing failed', $throwable);

            return (new PunchoutSessionStartResponseTransfer())
                ->setIsSuccess(false);
        }
    }

    protected function executeProcessLoginRequest(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        $requestUrl = $ociLoginRequestTransfer->getRequestUrlOrFail();

        $connectionTransfer = $this->repository->findActiveOciConnectionByRequestUrl($requestUrl);

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logRequestUrlFailure($requestUrl, PunchoutGatewayConstants::ERROR_CONNECTION_NOT_FOUND);

            return (new PunchoutSessionStartResponseTransfer())
                ->setIsSuccess(false)
                ->setErrorMessage(PunchoutGatewayConstants::ERROR_CONNECTION_NOT_FOUND);
        }

        $this->punchoutLogger->logConnectionFound($connectionTransfer);

        $processorPlugin = $this->resolveProcessorPlugin($connectionTransfer);

        $connectionTransfer = $processorPlugin->authenticate($ociLoginRequestTransfer, $connectionTransfer);

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($requestUrl, PunchoutGatewayConstants::ERROR_AUTHENTICATION_FAILED);

            return (new PunchoutSessionStartResponseTransfer())
                ->setIsSuccess(false);
        }

        $this->punchoutLogger->logAuthenticationSuccess($connectionTransfer);

        $ociLoginRequestTransfer->setIdPunchoutConnection($connectionTransfer->getIdPunchoutConnection());
        $ociLoginRequestTransfer->setIdStore($connectionTransfer->getIdStore());

        $setupRequestTransfer = $this->buildSetupRequestTransfer($ociLoginRequestTransfer, $connectionTransfer);

        $customerTransfer = $processorPlugin->resolveCustomer($setupRequestTransfer);

        if ($customerTransfer === null) {
            return (new PunchoutSessionStartResponseTransfer())
                ->setIsSuccess(false)
                ->setErrorMessage(PunchoutGatewayConstants::ERROR_CUSTOMER_NOT_RESOLVED);
        }

        $setupRequestTransfer->setCustomer($customerTransfer);

        $quoteTransfer = $processorPlugin->resolveQuote($setupRequestTransfer);

        $storeTransfer = $this->storeFacade->getStoreById($connectionTransfer->getIdStore());
        $quoteTransfer->setCustomer($customerTransfer);
        $quoteTransfer->setStore($storeTransfer);

        $quoteTransfer = $processorPlugin->expandQuote($quoteTransfer, $setupRequestTransfer);
        $quoteTransfer = $this->saveQuote($quoteTransfer);

        $this->punchoutLogger->logQuoteCreated($quoteTransfer);

        $punchoutSessionTransfer = new PunchoutSessionTransfer();
        $punchoutSessionTransfer->setIdQuote($quoteTransfer->getIdQuote());
        $punchoutSessionTransfer->setIdPunchoutConnection($connectionTransfer->getIdPunchoutConnection());
        $punchoutSessionTransfer->setIdCustomer($customerTransfer->getIdCustomer());

        $punchoutSessionTransfer = $processorPlugin->expandSession($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);

        $punchoutSessionTransfer = $this->entityManager->createPunchoutSession($punchoutSessionTransfer);

        $this->punchoutLogger->logSessionCreated($punchoutSessionTransfer);

        return (new PunchoutSessionStartResponseTransfer())
            ->setIsSuccess(true)
            ->setCustomer($customerTransfer)
            ->setQuote($quoteTransfer)
            ->setRedirectUrl($this->config->getOciDefaultStartUrl());
    }

    protected function resolveProcessorPlugin(PunchoutConnectionTransfer $connectionTransfer): PunchoutOciProcessorPluginInterface
    {
        $pluginClass = $connectionTransfer->getProcessorPluginClassOrFail();

        return new $pluginClass();
    }

    protected function buildSetupRequestTransfer(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): PunchoutSetupRequestTransfer {
        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $setupRequestTransfer->setProtocolType(PunchoutGatewayConstants::PROTOCOL_TYPE_OCI);
        $setupRequestTransfer->setConnection($connectionTransfer);
        $setupRequestTransfer->setIdStore($connectionTransfer->getIdStore());
        $setupRequestTransfer->setOciLoginRequest($ociLoginRequestTransfer);

        return $setupRequestTransfer;
    }

    protected function saveQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        if ($quoteTransfer->getIdQuote() !== null) {
            return $this->quoteFacade->updateQuote($quoteTransfer)->getQuoteTransfer();
        }

        return $this->quoteFacade->createQuote($quoteTransfer)->getQuoteTransfer();
    }
}
