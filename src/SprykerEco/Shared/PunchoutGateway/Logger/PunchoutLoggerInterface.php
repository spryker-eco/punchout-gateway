<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Shared\PunchoutGateway\Logger;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Throwable;

interface PunchoutLoggerInterface
{
    /**
     * Logs that a PunchOut setup request was received.
     */
    public function logRequestReceived(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void;

    /**
     * Logs that a PunchOut setup request was successfully parsed.
     */
    public function logRequestParsed(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void;

    /**
     * Logs an authentication attempt for the given sender identity.
     */
    public function logAuthenticationAttempt(string $senderIdentity): void;

    /**
     * Logs a successful authentication for a punchout connection.
     */
    public function logAuthenticationSuccess(PunchoutConnectionTransfer $punchoutConnectionTransfer): void;

    /**
     * Logs a successful detection of a punchout connection.
     */
    public function logConnectionFound(PunchoutConnectionTransfer $punchoutConnectionTransfer): void;

    /**
     * Logs a failed authentication attempt for the given sender identity.
     */
    public function logAuthenticationFailure(string $senderIdentity, string $reason): void;

    /**
     * Logs a failed request attempt for the given request URL.
     */
    public function logRequestUrlFailure(string $requestUrl, string $reason): void;

    /**
     * Logs that a response was generated.
     */
    public function logResponseGenerated(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): void;

    /**
     * Logs an error encountered during PunchOut processing.
     */
    public function logError(string $message, Throwable $throwable): void;

    /**
     * Logs successfully created quote.
     */
    public function logQuoteCreated(QuoteTransfer $quoteTransfer): void;

    /**
     * Logs successfully created punchout session.
     */
    public function logSessionCreated(PunchoutSessionTransfer $punchoutSessionTransfer): void;

    public function logSessionStartFailed(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void;

    public function logSessionStarted(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void;

    public function logGenericErrorMessage(string $message, array $context = []): void;

    public function logGenericInfoMessage(string $message, array $context = []): void;
}
