<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;

class PunchoutCxmlAuthenticator implements PunchoutCxmlAuthenticatorInterface
{
    protected const string FAILURE_REASON_INVALID_SECRET = 'Shared secret does not match';

    public function __construct(
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function authenticateConnection(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?PunchoutConnectionTransfer {
        $punchoutSetupRequestTransfer = $setupRequestTransfer->getCxmlSetupRequest();
        $connectionTransfer = $setupRequestTransfer->getConnection();

        $senderIdentity = $punchoutSetupRequestTransfer->getSenderIdentityOrFail();

        $this->punchoutLogger->logAuthenticationAttempt($senderIdentity);

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
}
