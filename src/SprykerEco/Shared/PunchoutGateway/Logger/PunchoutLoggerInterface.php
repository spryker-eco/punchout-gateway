<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Shared\PunchoutGateway\Logger;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Throwable;

interface PunchoutLoggerInterface
{
    public function logRequestReceived(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void;

    public function logRequestParsed(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void;

    public function logAuthenticationAttempt(string $senderIdentity): void;

    public function logAuthenticationSuccess(PunchoutConnectionTransfer $punchoutConnectionTransfer): void;

    public function logConnectionFound(PunchoutConnectionTransfer $punchoutConnectionTransfer): void;

    public function logAuthenticationFailure(string $senderIdentity, string $reason): void;

    public function logRequestUrlFailure(string $requestUrl, string $reason): void;

    public function logResponseGenerated(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): void;

    public function logThrowable(string $message, Throwable $throwable): void;

    public function logQuoteCreated(QuoteTransfer $quoteTransfer): void;

    public function logQuoteCreationFailed(QuoteResponseTransfer $quoteResponseTransfer): void;

    public function logSessionCreated(PunchoutSessionTransfer $punchoutSessionTransfer): void;

    public function logSessionStartFailed(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void;

    public function logSessionStarted(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void;

    /**
     * @param array<mixed> $context
     */
    public function logGenericErrorMessage(string $message, array $context = []): void;

    /**
     * @param array<mixed> $context
     */
    public function logGenericInfoMessage(string $message, array $context = []): void;
}
