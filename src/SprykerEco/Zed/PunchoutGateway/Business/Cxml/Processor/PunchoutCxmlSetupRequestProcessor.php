<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Processor;

use Exception;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Business\Model\ProcessorPluginResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\QuoteCreatorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Session\SessionCreatorInterface;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutCxmlProcessorPluginInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class PunchoutCxmlSetupRequestProcessor implements PunchoutCxmlSetupRequestProcessorInterface
{
    protected const string ERROR_SERVER_IDENTITY_MISSING = 'Server identity is missing or empty in the request.';

    protected const string PUNCH_OUT_C_XML_SETUP_REQUEST_PROCESSING_FAILED = 'PunchOut cXML setup request processing failed';

    protected const string STATUS_CODE_ERROR = '500';

    public function __construct(
        protected PunchoutGatewayServiceInterface $punchoutGatewayService,
        protected QuoteCreatorInterface $quoteCreator,
        protected SessionCreatorInterface $sessionCreator,
        protected ProcessorPluginResolverInterface $processorPluginResolver,
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayRepositoryInterface $repository,
    ) {
    }

    public function processSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        try {
            return $this->executeProcessSetupRequest($punchoutCxmlSetupRequestTransfer);
        } catch (Exception $exception) {
            $this->punchoutLogger->logThrowable(static::PUNCH_OUT_C_XML_SETUP_REQUEST_PROCESSING_FAILED, $exception);

            return $this->createErrorResponse(static::PUNCH_OUT_C_XML_SETUP_REQUEST_PROCESSING_FAILED);
        }
    }

    protected function executeProcessSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        $this->punchoutLogger->logRequestReceived($punchoutCxmlSetupRequestTransfer);

        $cxml = $this->punchoutGatewayService->decodeCxml($punchoutCxmlSetupRequestTransfer->getRawXmlOrFail());
        $senderIdentity = $cxml->header?->sender?->credential?->identity;

        if ($senderIdentity === null || $senderIdentity === '') {
            $this->punchoutLogger->logAuthenticationFailure('', static::ERROR_SERVER_IDENTITY_MISSING);

            return $this->createErrorResponse(
                static::ERROR_SERVER_IDENTITY_MISSING,
            );
        }

        $connectionTransfer = $this->repository->findActiveCxmlConnectionBySenderIdentity($senderIdentity);

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($senderIdentity, PunchoutGatewayConfig::ERROR_CONNECTION_NOT_FOUND);

            return $this->createErrorResponse(
                PunchoutGatewayConfig::ERROR_CONNECTION_NOT_FOUND,
            );
        }

        $this->punchoutLogger->logConnectionFound($connectionTransfer);

        $processorPlugin = $this->processorPluginResolver->resolveProcessorPlugin($connectionTransfer, PunchoutCxmlProcessorPluginInterface::class);

        $punchoutCxmlSetupRequestTransfer = $processorPlugin->parseCxmlRequest($punchoutCxmlSetupRequestTransfer, $cxml);

        $this->punchoutLogger->logRequestParsed($punchoutCxmlSetupRequestTransfer);

        $setupRequestTransfer = $this->buildSetupRequest($punchoutCxmlSetupRequestTransfer, $connectionTransfer);

        $connectionTransfer = $processorPlugin->authenticate($setupRequestTransfer);

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($senderIdentity, PunchoutGatewayConfig::ERROR_AUTHENTICATION_FAILED);

            return $this->createErrorResponse(
                PunchoutGatewayConfig::ERROR_AUTHENTICATION_FAILED,
            );
        }

        $customerTransfer = $processorPlugin->resolveCustomer($setupRequestTransfer);

        if ($customerTransfer === null) {
            $this->punchoutLogger->logGenericErrorMessage(PunchoutGatewayConfig::ERROR_CUSTOMER_NOT_RESOLVED);

            return $this->createErrorResponse(
                PunchoutGatewayConfig::ERROR_CUSTOMER_NOT_RESOLVED,
            );
        }

        $this->punchoutLogger->logGenericInfoMessage('Customer was resolved.', [
            PunchoutSessionStartResponseTransfer::CUSTOMER => $customerTransfer->getCustomerReference(),
        ]);

        $setupRequestTransfer->setCustomer($customerTransfer);

        $setupRequestTransfer = $this->quoteCreator->createQuote($processorPlugin, $setupRequestTransfer);

        if ($setupRequestTransfer->getQuote() === null) {
            return $this->createErrorResponse(
                PunchoutGatewayConfig::ERROR_QUOTE_WAS_NOT_CREATED,
            );
        }

        $punchoutSessionTransfer = $this->sessionCreator->createSession($processorPlugin, $setupRequestTransfer);

        $responseTransfer = $this->createSuccessResponse();

        $responseTransfer = $processorPlugin->expandResponse($punchoutSessionTransfer, $responseTransfer, $punchoutCxmlSetupRequestTransfer);

        $this->punchoutLogger->logResponseGenerated($responseTransfer);

        return $responseTransfer;
    }

    protected function buildSetupRequest(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): PunchoutSetupRequestTransfer {
        $cxmlSetupRequestTransfer->setIdPunchoutConnection($connectionTransfer->getIdPunchoutConnection());
        $cxmlSetupRequestTransfer->setIdStore($connectionTransfer->getIdStore());

        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $setupRequestTransfer->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_CXML);
        $setupRequestTransfer->setConnection($connectionTransfer);
        $setupRequestTransfer->setIdStore($connectionTransfer->getIdStore());
        $setupRequestTransfer->setCxmlSetupRequest($cxmlSetupRequestTransfer);

        return $setupRequestTransfer;
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
        $responseTransfer->setStatusCode(static::STATUS_CODE_ERROR);
        $responseTransfer->setStatusText($errorMessage);
        $responseTransfer->setErrorMessage($errorMessage);

        $this->punchoutLogger->logResponseGenerated($responseTransfer);

        return $responseTransfer;
    }
}
