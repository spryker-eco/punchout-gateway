<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Processor;

use CXml\Serializer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutCxmlProcessorPluginInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;
use Throwable;

class PunchoutCxmlSetupRequestProcessor implements PunchoutCxmlSetupRequestProcessorInterface
{
    protected const string ERROR_SERVER_IDENTITY_MISSING = 'Server identity is missing or empty in the request.';

    public function __construct(
        protected QuoteFacadeInterface $quoteFacade,
        protected StoreFacadeInterface $storeFacade,
        protected Serializer $cxmlSerializer,
        protected PunchoutGatewayEntityManagerInterface $entityManager,
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayRepositoryInterface $repository,
    ) {
    }

    public function processSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        try {
            return $this->executeProcessSetupRequest($punchoutCxmlSetupRequestTransfer);
        } catch (Throwable $throwable) {
            $this->punchoutLogger->logError('PunchOut cXML setup request processing failed', $throwable);

            return $this->createErrorResponse(
                $throwable->getMessage(),
            );
        }
    }

    protected function executeProcessSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        $this->punchoutLogger->logRequestReceived($punchoutCxmlSetupRequestTransfer);

        $cxml = $this->cxmlSerializer->deserialize($punchoutCxmlSetupRequestTransfer->getRawXmlOrFail());
        $senderIdentity = $cxml->header?->sender?->credential?->identity;

        if ($senderIdentity === null) {
            $this->punchoutLogger->logAuthenticationFailure($senderIdentity, static::ERROR_SERVER_IDENTITY_MISSING);

            return $this->createErrorResponse(
                static::ERROR_SERVER_IDENTITY_MISSING,
            );
        }

        $connectionTransfer = $this->repository->findActiveCxmlConnectionBySenderIdentity($senderIdentity);

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($senderIdentity, PunchoutGatewayConstants::ERROR_CONNECTION_NOT_FOUND);

            return $this->createErrorResponse(
                PunchoutGatewayConstants::ERROR_CONNECTION_NOT_FOUND,
            );
        }

        $this->punchoutLogger->logConnectionFound($connectionTransfer);

        $processorPlugin = $this->resolveProcessorPlugin($connectionTransfer);

        $punchoutCxmlSetupRequestTransfer = $processorPlugin->parseCxmlRequest($punchoutCxmlSetupRequestTransfer, $cxml);

        $this->punchoutLogger->logRequestParsed($punchoutCxmlSetupRequestTransfer);

        $connectionTransfer = $processorPlugin->authenticate($punchoutCxmlSetupRequestTransfer, $connectionTransfer);

        if ($connectionTransfer === null) {
            return $this->createErrorResponse(
                PunchoutGatewayConstants::ERROR_AUTHENTICATION_FAILED,
            );
        }

        $setupRequestTransfer = $this->buildSetupRequest($punchoutCxmlSetupRequestTransfer, $connectionTransfer);

        $customerTransfer = $processorPlugin->resolveCustomer($setupRequestTransfer);

        if ($customerTransfer === null) {
            return $this->createErrorResponse(
                PunchoutGatewayConstants::ERROR_CUSTOMER_NOT_RESOLVED,
            );
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

        $this->entityManager->deletePunchoutSessionIfExists($punchoutSessionTransfer);
        $punchoutSessionTransfer = $this->entityManager->createPunchoutSession($punchoutSessionTransfer);

        $this->punchoutLogger->logSessionCreated($punchoutSessionTransfer);

        $responseTransfer = $this->createSuccessResponse();

        $responseTransfer = $processorPlugin->expandResponse($punchoutSessionTransfer, $responseTransfer, $punchoutCxmlSetupRequestTransfer);

        $this->punchoutLogger->logResponseGenerated($responseTransfer);

        return $responseTransfer;
    }

    protected function resolveProcessorPlugin(PunchoutConnectionTransfer $connectionTransfer): PunchoutCxmlProcessorPluginInterface
    {
        $pluginClass = $connectionTransfer->getProcessorPluginClassOrFail();

        return new $pluginClass();
    }

    protected function buildSetupRequest(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): PunchoutSetupRequestTransfer {
        $cxmlSetupRequestTransfer->setIdPunchoutConnection($connectionTransfer->getIdPunchoutConnection());
        $cxmlSetupRequestTransfer->setIdStore($connectionTransfer->getIdStore());

        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $setupRequestTransfer->setProtocolType(PunchoutGatewayConstants::PROTOCOL_TYPE_CXML);
        $setupRequestTransfer->setConnection($connectionTransfer);
        $setupRequestTransfer->setIdStore($connectionTransfer->getIdStore());
        $setupRequestTransfer->setCxmlSetupRequest($cxmlSetupRequestTransfer);

        return $setupRequestTransfer;
    }

    protected function saveQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        if ($quoteTransfer->getIdQuote() !== null) {
            return $this->quoteFacade->updateQuote($quoteTransfer)->getQuoteTransfer();
        }

        return $this->quoteFacade->createQuote($quoteTransfer)->getQuoteTransfer();
    }

    protected function createSuccessResponse(): PunchoutSetupResponseTransfer
    {
        $responseTransfer = new PunchoutSetupResponseTransfer();
        $responseTransfer->setIsSuccess(true);

        return $responseTransfer;
    }

    protected function createErrorResponse(
        string $errorMessage,
    ): PunchoutSetupResponseTransfer {
        $responseTransfer = new PunchoutSetupResponseTransfer();
        $responseTransfer->setIsSuccess(false);
        $responseTransfer->setErrorMessage($errorMessage);

        $this->punchoutLogger->logResponseGenerated($responseTransfer);

        return $responseTransfer;
    }
}
