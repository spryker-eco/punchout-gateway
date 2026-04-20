<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Session;

use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;

class SessionCreator implements SessionCreatorInterface
{
    public function __construct(
        protected PunchoutLoggerInterface $punchoutLogger,
        protected PunchoutGatewayEntityManagerInterface $entityManager,
    ) {
    }

    public function createSession(
        PunchoutProcessorPluginInterface $processorPlugin,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?PunchoutSessionTransfer {
        $punchoutSessionTransfer = new PunchoutSessionTransfer();
        $punchoutSessionTransfer->setIdQuote($setupRequestTransfer->getQuote()->getIdQuote());
        $punchoutSessionTransfer->setIdPunchoutConnection($setupRequestTransfer->getConnection()->getIdPunchoutConnection());
        $punchoutSessionTransfer->setIdCustomer($setupRequestTransfer->getCustomer()->getIdCustomer());
        $punchoutSessionTransfer->setPunchoutData(new PunchoutSessionDataTransfer());

        $punchoutSessionTransfer = $processorPlugin->resolveSession($punchoutSessionTransfer, $setupRequestTransfer, $setupRequestTransfer->getQuote());

        if (!$punchoutSessionTransfer) {
            $this->punchoutLogger->logGenericErrorMessage(PunchoutGatewayConfig::ERROR_SESSION_CREATION_FAILED);

            return null;
        }

        $this->deleteExistingSession($punchoutSessionTransfer);

        $punchoutSessionTransfer = $this->entityManager->createPunchoutSession($punchoutSessionTransfer);

        $this->punchoutLogger->logSessionCreated($punchoutSessionTransfer);

        return $punchoutSessionTransfer;
    }

    protected function deleteExistingSession(PunchoutSessionTransfer $punchoutSessionTransfer): void
    {
        if ($punchoutSessionTransfer->getBuyerCookie()) {
            $this->entityManager->deletePunchoutSessionByBuyerCookie($punchoutSessionTransfer);

            return;
        }

        if ($punchoutSessionTransfer->getIdPunchoutSession()) {
            $this->entityManager->deletePunchoutSessionById($punchoutSessionTransfer);

            return;
        }

        if ($punchoutSessionTransfer->getSessionToken()) {
            $this->entityManager->deletePunchoutSessionByToken($punchoutSessionTransfer);

            return;
        }
    }
}
