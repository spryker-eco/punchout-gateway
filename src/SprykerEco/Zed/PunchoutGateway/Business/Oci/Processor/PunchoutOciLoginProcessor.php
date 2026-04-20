<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Processor;

use Exception;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Business\Model\ProcessorPluginResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\QuoteCreatorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Session\SessionCreatorInterface;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutOciLoginProcessor implements PunchoutOciLoginProcessorInterface
{
    protected const string PUNCH_OUT_OCI_LOGIN_PROCESSING_FAILED = 'PunchOut OCI login processing failed';

    public function __construct(
        protected QuoteCreatorInterface $quoteCreator,
        protected SessionCreatorInterface $sessionCreator,
        protected ProcessorPluginResolverInterface $processorPluginResolver,
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayRepositoryInterface $repository,
        protected PunchoutGatewayConfig $config,
    ) {
    }

    public function processLoginRequest(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        try {
            return $this->executeProcessLoginRequest($ociLoginRequestTransfer);
        } catch (Exception $exception) {
            $this->punchoutLogger->logThrowable(static::PUNCH_OUT_OCI_LOGIN_PROCESSING_FAILED, $exception);

            return $this->createErrorResponse(static::PUNCH_OUT_OCI_LOGIN_PROCESSING_FAILED);
        }
    }

    protected function executeProcessLoginRequest(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        $requestUrl = $ociLoginRequestTransfer->getRequestUrlOrFail();

        $connectionTransfer = $this->repository->findActiveOciConnectionByRequestUrl($requestUrl);

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logRequestUrlFailure($requestUrl, SharedPunchoutGatewayConfig::ERROR_CONNECTION_NOT_FOUND);

            return $this->createErrorResponse(SharedPunchoutGatewayConfig::ERROR_CONNECTION_NOT_FOUND);
        }

        $this->punchoutLogger->logConnectionFound($connectionTransfer);

        $processorPlugin = $this->processorPluginResolver->resolveProcessorPlugin($connectionTransfer, PunchoutProcessorPluginInterface::class);

        $setupRequestTransfer = $this->buildSetupRequestTransfer($ociLoginRequestTransfer, $connectionTransfer);

        $connectionTransfer = $processorPlugin->authenticate($setupRequestTransfer);

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($requestUrl, SharedPunchoutGatewayConfig::ERROR_AUTHENTICATION_FAILED);

            return $this->createErrorResponse(SharedPunchoutGatewayConfig::ERROR_AUTHENTICATION_FAILED);
        }

        $this->punchoutLogger->logAuthenticationSuccess($connectionTransfer);

        $ociLoginRequestTransfer->setIdPunchoutConnection($connectionTransfer->getIdPunchoutConnection());
        $ociLoginRequestTransfer->setIdStore($connectionTransfer->getIdStore());

        $customerTransfer = $processorPlugin->resolveCustomer($setupRequestTransfer);

        if ($customerTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($requestUrl, SharedPunchoutGatewayConfig::ERROR_CUSTOMER_NOT_RESOLVED);

            return $this->createErrorResponse(SharedPunchoutGatewayConfig::ERROR_CUSTOMER_NOT_RESOLVED);
        }

        $setupRequestTransfer->setCustomer($customerTransfer);

        $setupRequestTransfer = $this->quoteCreator->createQuote($processorPlugin, $setupRequestTransfer);

        if ($setupRequestTransfer->getQuote() === null) {
            return $this->createErrorResponse(SharedPunchoutGatewayConfig::ERROR_QUOTE_WAS_NOT_CREATED);
        }

        $punchoutSessionTransfer = $this->sessionCreator->createSession($processorPlugin, $setupRequestTransfer);

        if (!$punchoutSessionTransfer) {
            $this->punchoutLogger->logGenericErrorMessage(SharedPunchoutGatewayConfig::ERROR_SESSION_CREATION_FAILED);

            return $this->createErrorResponse(SharedPunchoutGatewayConfig::ERROR_SESSION_CREATION_FAILED);
        }

        return (new PunchoutSessionStartResponseTransfer())
            ->setIsSuccess(true)
            ->setCustomer($customerTransfer)
            ->setQuote($setupRequestTransfer->getQuote()->setPunchoutSession($punchoutSessionTransfer))
            ->setRedirectUrl($this->config->getOciDefaultStartUrl())
            ->setStoreName($setupRequestTransfer->getQuote()->getStore()->getName());
    }

    protected function buildSetupRequestTransfer(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): PunchoutSetupRequestTransfer {
        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $setupRequestTransfer->setProtocolType(SharedPunchoutGatewayConfig::PROTOCOL_TYPE_OCI);
        $setupRequestTransfer->setConnection($connectionTransfer);
        $setupRequestTransfer->setIdStore($connectionTransfer->getIdStore());
        $setupRequestTransfer->setOciLoginRequest($ociLoginRequestTransfer);

        return $setupRequestTransfer;
    }

    protected function createErrorResponse(string $message): PunchoutSessionStartResponseTransfer
    {
        return (new PunchoutSessionStartResponseTransfer())
            ->setIsSuccess(false)
            ->setErrorMessage($message);
    }
}
