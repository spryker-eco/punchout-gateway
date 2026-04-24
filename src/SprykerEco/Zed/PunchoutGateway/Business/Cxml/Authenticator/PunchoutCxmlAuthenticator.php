<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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

        return password_verify($providedSecret, $storedSecret);
    }
}
