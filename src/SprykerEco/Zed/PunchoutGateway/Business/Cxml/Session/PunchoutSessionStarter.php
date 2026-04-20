<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session;

use Generated\Shared\Transfer\CustomerCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutSessionStarter implements PunchoutSessionStarterInterface
{
    protected const string ERROR_SESSION_INVALID = 'Punchout session is invalid or expired';

    protected const string ERROR_CUSTOMER_NOT_FOUND = 'Customer not found';

    public function __construct(
        protected CustomerFacadeInterface $customerFacade,
        protected QuoteFacadeInterface $quoteFacade,
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayRepositoryInterface $repository,
        protected PunchoutGatewayConfig $config,
    ) {
    }

    public function startSession(
        PunchoutSessionStartRequestTransfer $sessionStartRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        $this->punchoutLogger->logGenericInfoMessage('Starting session processing...');

        $punchoutSessionTransfer = $this->repository->findValidPunchoutSessionByToken(
            $sessionStartRequestTransfer->getSessionTokenOrFail(),
        );

        if ($punchoutSessionTransfer === null) {
            $this->punchoutLogger->logGenericErrorMessage(
                'Valid session was not found.',
                ['token' => $sessionStartRequestTransfer->getSessionToken()],
            );

            return $this->createErrorResponse(static::ERROR_SESSION_INVALID);
        }

        if (!$punchoutSessionTransfer->getIdQuote()) {
            $this->punchoutLogger->logGenericInfoMessage(
                'Quote was not created for this session.',
            );
        }

        $customerCriteriaTransfer = (new CustomerCriteriaTransfer())
            ->setIdCustomer($punchoutSessionTransfer->getIdCustomer())
            ->setWithExpanders(true);

        $customerResponseTransfer = $this->customerFacade->getCustomerByCriteria($customerCriteriaTransfer);

        if (!$customerResponseTransfer->getIsSuccess() || !$customerResponseTransfer->getHasCustomer()) {
            $this->punchoutLogger->logGenericErrorMessage(
                SharedPunchoutGatewayConfig::ERROR_CUSTOMER_NOT_RESOLVED,
            );

            return $this->createErrorResponse(static::ERROR_CUSTOMER_NOT_FOUND);
        }

        $customerTransfer = $customerResponseTransfer->getCustomerTransferOrFail();
        $storeName = $punchoutSessionTransfer->getConnectionOrFail()->getStoreNameOrFail();

        $responseTransfer = new PunchoutSessionStartResponseTransfer();
        $responseTransfer->setIsSuccess(true);
        $responseTransfer->setCustomer($customerTransfer);
        $responseTransfer->setStoreName($storeName);

        if ($punchoutSessionTransfer->getIdQuote()) {
            $quoteResponseTransfer = $this->quoteFacade->findQuoteById($punchoutSessionTransfer->getIdQuote());

            if (!$quoteResponseTransfer->getIsSuccessful()) {
                $this->punchoutLogger->logGenericErrorMessage(
                    'Quote was not found for this session.',
                );

                return (new PunchoutSessionStartResponseTransfer())
                    ->setIsSuccess(false);
            }

            $quoteTransfer = $quoteResponseTransfer->getQuoteTransfer();
            $quoteTransfer->setCustomer($customerTransfer);
            $responseTransfer->setQuote($quoteTransfer);
        }

        $this->punchoutLogger->logGenericInfoMessage('Session processing is completed.');

        return $responseTransfer;
    }

    protected function createErrorResponse(string $errorMessage): PunchoutSessionStartResponseTransfer
    {
        $responseTransfer = new PunchoutSessionStartResponseTransfer();
        $responseTransfer->setIsSuccess(false);
        $responseTransfer->setErrorMessage($errorMessage);

        return $responseTransfer;
    }
}
