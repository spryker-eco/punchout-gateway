<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session;

use Generated\Shared\Transfer\CustomerCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionConditionsTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutSessionStarter implements PunchoutSessionStarterInterface
{
    protected const string ERROR_SESSION_INVALID = 'Punchout session is invalid or expired';

    protected const string ERROR_CUSTOMER_NOT_FOUND = 'Customer not found';

    public function __construct(
        protected CustomerFacadeInterface $customerFacade,
        protected QuoteFacadeInterface $quoteFacade,
        protected StoreFacadeInterface $storeFacade,
        protected PunchoutGatewayConfig $config,
        protected PunchoutGatewayEntityManagerInterface $entityManager,
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayRepositoryInterface $repository,
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
                $this->repository->findValidPunchoutSessionByToken(
                    $sessionStartRequestTransfer->getSessionToken(),
                )?->toArray() ?? [],
            );

            return $this->createErrorResponse(static::ERROR_SESSION_INVALID);
        }

        $customerCriteriaTransfer = (new CustomerCriteriaTransfer())
            ->setIdCustomer($punchoutSessionTransfer->getIdCustomer())
            ->setWithExpanders(true);

        $customerResponseTransfer = $this->customerFacade->getCustomerByCriteria($customerCriteriaTransfer);

        if (!$customerResponseTransfer->getIsSuccess() || !$customerResponseTransfer->getHasCustomer()) {
            $this->punchoutLogger->logGenericErrorMessage(
                PunchoutGatewayConstants::ERROR_CUSTOMER_NOT_RESOLVED,
            );

            return $this->createErrorResponse(static::ERROR_CUSTOMER_NOT_FOUND);
        }

        $customerTransfer = $customerResponseTransfer->getCustomerTransferOrFail();
        $storeName = $this->resolveStoreName($punchoutSessionTransfer->getIdPunchoutConnection());

        if (!$punchoutSessionTransfer->getIdQuote()) {
            $this->punchoutLogger->logGenericInfoMessage(
                'Quote was not created for this session.',
            );
        }

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

        if ($this->config->isCxmlSessionDeletedOnStart()) {
            $this->punchoutLogger->logGenericInfoMessage(
                'Session is deleted to invalidate the login URL.',
            );

            $this->entityManager->deletePunchoutSessionIfExists($punchoutSessionTransfer);
        }

        $this->punchoutLogger->logGenericInfoMessage('Session processing is completed.');

        return $responseTransfer;
    }

    protected function resolveStoreName(int $idPunchoutConnection): string
    {
        $criteriaTransfer = new PunchoutConnectionCriteriaTransfer();
        $conditionsTransfer = new PunchoutConnectionConditionsTransfer();
        $conditionsTransfer->addIdPunchoutConnection($idPunchoutConnection);
        $criteriaTransfer->setPunchoutConnectionConditions($conditionsTransfer);

        $connectionCollection = $this->repository->getPunchoutConnectionCollection($criteriaTransfer);
        /** @var \Generated\Shared\Transfer\PunchoutConnectionTransfer $connectionTransfer */
        $connectionTransfer = $connectionCollection->getPunchoutConnections()->offsetGet(0);

        $storeTransfer = $this->storeFacade->getStoreById($connectionTransfer->getIdStore());

        return $storeTransfer->getNameOrFail();
    }

    protected function createErrorResponse(string $errorMessage): PunchoutSessionStartResponseTransfer
    {
        $responseTransfer = new PunchoutSessionStartResponseTransfer();
        $responseTransfer->setIsSuccess(false);
        $responseTransfer->setErrorMessage($errorMessage);

        return $responseTransfer;
    }
}
