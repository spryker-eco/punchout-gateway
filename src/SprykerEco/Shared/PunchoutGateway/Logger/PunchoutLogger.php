<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Shared\PunchoutGateway\Logger;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteErrorTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Throwable;

class PunchoutLogger implements PunchoutLoggerInterface
{
    use LoggerTrait;

    public function logRequestReceived(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void
    {
        $this->getLogger()->info('PunchOut setup request received', [
            'request_url' => $punchoutSetupRequestTransfer->getRequestUrl(),
        ]);
    }

    public function logRequestParsed(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void
    {
        $this->getLogger()->info('PunchOut setup request parsed', [
            'sender_identity' => $punchoutSetupRequestTransfer->getSenderIdentity(),
            'operation' => $punchoutSetupRequestTransfer->getOperation(),
            'payload_id' => $punchoutSetupRequestTransfer->getPayloadId(),
        ]);
    }

    public function logAuthenticationAttempt(string $senderIdentity): void
    {
        $this->getLogger()->info('PunchOut authentication attempt', [
            'sender_identity' => $senderIdentity,
        ]);
    }

    public function logAuthenticationSuccess(PunchoutConnectionTransfer $punchoutConnectionTransfer): void
    {
        $this->getLogger()->info('PunchOut authentication successful', [
            'connection_id' => $punchoutConnectionTransfer->getIdPunchoutConnection(),
            'connection_name' => $punchoutConnectionTransfer->getName(),
        ]);
    }

    public function logConnectionFound(PunchoutConnectionTransfer $punchoutConnectionTransfer): void
    {
        $context = $punchoutConnectionTransfer->toArray();
        unset($context[PunchoutConnectionTransfer::PROTOCOL_CONFIGURATION]);

        $this->getLogger()->info('PunchOut connection was found.', $context);
    }

    public function logAuthenticationFailure(string $senderIdentity, string $reason): void
    {
        $this->getLogger()->warning('PunchOut authentication failed', [
            'sender_identity' => $senderIdentity,
            'reason' => $reason,
        ]);
    }

    public function logRequestUrlFailure(string $requestUrl, string $reason): void
    {
        $this->getLogger()->warning('PunchOut requested from unidentified server', [
            'request_url' => $requestUrl,
            'reason' => $reason,
        ]);
    }

    public function logResponseGenerated(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): void
    {
        $this->getLogger()->info('PunchOut response generated', [
            'is_success' => $punchoutSetupResponseTransfer->getIsSuccess(),
        ]);
    }

    public function logQuoteCreated(QuoteTransfer $quoteTransfer): void
    {
        $this->getLogger()->info('PunchOut quote was created', [
            'quoteReference' => $quoteTransfer->getUuid(),
        ]);
    }

    public function logQuoteCreationFailed(QuoteResponseTransfer $quoteResponseTransfer): void
    {
        $this->getLogger()->info('PunchOut quote was not created', [
            'errors' => array_map(fn (QuoteErrorTransfer $r) => $r->toArray(), $quoteResponseTransfer->getErrors()->getArrayCopy()),
        ]);
    }

    public function logSessionCreated(PunchoutSessionTransfer $punchoutSessionTransfer): void
    {
        $this->getLogger()->info('PunchOut session was created', [
            'idPunchoutSession' => $punchoutSessionTransfer->getIdPunchoutSession(),
        ]);
    }

    public function logSessionStartFailed(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void
    {
        $this->getLogger()->error(
            'PunchOut session start failed',
            $this->sanitizeContext($sessionStartResponseTransfer->toArray()),
        );
    }

    public function logSessionStarted(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void
    {
        $this->getLogger()->info(
            'PunchOut session start successfully',
            $this->sanitizeContext($sessionStartResponseTransfer->toArray()),
        );
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function sanitizeContext(array $context): array
    {
        $context = $this->sanitizeCustomerData($context);

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function sanitizeCustomerData(array $context): array
    {
        if (isset($context[PunchoutSessionStartResponseTransfer::CUSTOMER]) && is_array($context[PunchoutSessionStartResponseTransfer::CUSTOMER])) {
            $context[PunchoutSessionStartResponseTransfer::CUSTOMER] = array_intersect_key(array_flip([
                CustomerTransfer::ID_CUSTOMER,
                CustomerTransfer::EMAIL,
                CustomerTransfer::CUSTOMER_REFERENCE,
            ]), $context[PunchoutSessionStartResponseTransfer::CUSTOMER]);
        }

        return $context;
    }

    /**
     * {@inheritDoc}
     */
    public function logGenericErrorMessage(string $message, array $context = []): void
    {
        $this->getLogger()->error(
            $message,
            $this->sanitizeContext($context),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function logGenericInfoMessage(string $message, array $context = []): void
    {
        $this->getLogger()->info(
            $message,
            $this->sanitizeContext($context),
        );
    }

    public function logThrowable(string $message, Throwable $throwable): void
    {
        $this->getLogger()->error($message, [
            'exception_message' => $throwable->getMessage(),
            'exception_class' => $throwable::class,
        ]);
    }
}
