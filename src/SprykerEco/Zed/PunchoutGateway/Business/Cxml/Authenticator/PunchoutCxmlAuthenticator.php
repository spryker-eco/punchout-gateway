<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionConditionsTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class PunchoutCxmlAuthenticator implements PunchoutCxmlAuthenticatorInterface
{
    protected const string FAILURE_REASON_INVALID_SECRET = 'Shared secret does not match';

    protected const string FAILURE_REASON_INVALID_REQUEST_URL = 'Request URL does not match connection';

    public function __construct(
        protected PunchoutGatewayRepositoryInterface $punchoutGatewayRepository,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function authenticate(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): ?PunchoutConnectionTransfer {
        $senderIdentity = $punchoutSetupRequestTransfer->getSenderIdentityOrFail();

        $this->punchoutLogger->logAuthenticationAttempt($senderIdentity);

        $punchoutConnectionConditionsTransfer = new PunchoutConnectionConditionsTransfer();
        $punchoutConnectionConditionsTransfer->addSenderIdentity($senderIdentity);
        $punchoutConnectionConditionsTransfer->addProtocolType(PunchoutGatewayConstants::PROTOCOL_TYPE_CXML);
        $punchoutConnectionConditionsTransfer->setIsActive(true);

        $punchoutConnectionCriteriaTransfer = new PunchoutConnectionCriteriaTransfer();
        $punchoutConnectionCriteriaTransfer->setPunchoutConnectionConditions($punchoutConnectionConditionsTransfer);

        $connectionCollectionTransfer = $this->punchoutGatewayRepository
            ->getPunchoutConnectionCollection($punchoutConnectionCriteriaTransfer);

        if ($connectionCollectionTransfer->getPunchoutConnections()->count() === 0) {
            $this->punchoutLogger->logAuthenticationFailure($senderIdentity, PunchoutGatewayConstants::ERROR_CONNECTION_NOT_FOUND);

            return null;
        }

        foreach ($connectionCollectionTransfer->getPunchoutConnections() as $connectionTransfer) {
            if (!$this->isAllowedRequestUrl($connectionTransfer, $punchoutSetupRequestTransfer)) {
                $this->punchoutLogger->logRequestUrlFailure($punchoutSetupRequestTransfer->getRequestUrl(), static::FAILURE_REASON_INVALID_SECRET);

                continue;
            }

            if (!$this->isSecretValid($connectionTransfer, $punchoutSetupRequestTransfer)) {
                $this->punchoutLogger->logAuthenticationFailure($senderIdentity, static::FAILURE_REASON_INVALID_SECRET);

                continue;
            }

            $this->punchoutLogger->logAuthenticationSuccess($connectionTransfer);

            return $connectionTransfer;
        }

        return null;
    }

    public function authenticateConnection(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): ?PunchoutConnectionTransfer {
        $senderIdentity = $punchoutSetupRequestTransfer->getSenderIdentityOrFail();

        $this->punchoutLogger->logAuthenticationAttempt($senderIdentity);

        if (!$this->isAllowedRequestUrl($connectionTransfer, $punchoutSetupRequestTransfer)) {
            $this->punchoutLogger->logRequestUrlFailure($punchoutSetupRequestTransfer->getRequestUrl(), static::FAILURE_REASON_INVALID_REQUEST_URL);

            return null;
        }

        if (!$this->isSecretValid($connectionTransfer, $punchoutSetupRequestTransfer)) {
            $this->punchoutLogger->logAuthenticationFailure($senderIdentity, static::FAILURE_REASON_INVALID_SECRET);

            return null;
        }

        $this->punchoutLogger->logAuthenticationSuccess($connectionTransfer);

        return $connectionTransfer;
    }

    protected function isSecretValid(
        PunchoutConnectionTransfer $connectionTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): bool {
        $storedSecret = $connectionTransfer->getCxmlConfigurationOrFail()->getSenderSharedSecretOrFail();
        $providedSecret = $punchoutSetupRequestTransfer->getSenderSharedSecretOrFail();

        return hash_equals($storedSecret, $providedSecret);
    }

    protected function isAllowedRequestUrl(PunchoutConnectionTransfer $connectionTransfer, PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): bool
    {
        return str_starts_with($punchoutSetupRequestTransfer->getRequestUrl(), $connectionTransfer->getRequestUrl());
    }
}
