<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Shared\PunchoutGateway\Logger;

use Generated\Shared\Transfer\AuditLoggerConfigCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Shared\Log\LoggerTrait;
use Throwable;

class PunchoutLogger implements PunchoutLoggerInterface
{
//    use AuditLoggerTrait;
    use LoggerTrait;

    protected function getAuditLogger(AuditLoggerConfigCriteriaTransfer $createAuditLoggerConfigCriteria): LoggerInterface
    {
        return $this->getLogger();
    }

    protected const string AUDIT_CHANNEL = 'punchout_gateway';

    public function logRequestReceived(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut setup request received', [
            'request_url' => $punchoutSetupRequestTransfer->getRequestUrl(),
        ]);
    }

    public function logRequestParsed(PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut setup request parsed', [
            'sender_identity' => $punchoutSetupRequestTransfer->getSenderIdentity(),
            'operation' => $punchoutSetupRequestTransfer->getOperation(),
            'payload_id' => $punchoutSetupRequestTransfer->getPayloadId(),
        ]);
    }

    public function logAuthenticationAttempt(string $senderIdentity): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut authentication attempt', [
            'sender_identity' => $senderIdentity,
        ]);
    }

    public function logAuthenticationSuccess(PunchoutConnectionTransfer $punchoutConnectionTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut authentication successful', [
            'connection_id' => $punchoutConnectionTransfer->getIdPunchoutConnection(),
            'connection_name' => $punchoutConnectionTransfer->getName(),
        ]);
    }

    public function logConnectionFound(PunchoutConnectionTransfer $punchoutConnectionTransfer): void
    {
        $context = $punchoutConnectionTransfer->toArray();
        unset($context[PunchoutConnectionTransfer::PROTOCOL_CONFIGURATION]);

        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut connection was found.', $context);
    }

    public function logAuthenticationFailure(string $senderIdentity, string $reason): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->warning('PunchOut authentication failed', [
            'sender_identity' => $senderIdentity,
            'reason' => $reason,
        ]);
    }

    public function logRequestUrlFailure(string $requestUrl, string $reason): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->warning('PunchOut requested from unidentified server', [
            'requestUrl' => $requestUrl,
            'reason' => $reason,
        ]);
    }

    public function logResponseGenerated(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut response generated', [
            'is_success' => $punchoutSetupResponseTransfer->getIsSuccess(),
        ]);
    }

    public function logQuoteCreated(QuoteTransfer $quoteTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut quote was created', [
            'quoteReference' => $quoteTransfer->getUuid(),
        ]);
    }

    public function logSessionCreated(PunchoutSessionTransfer $punchoutSessionTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info('PunchOut session was created', [
            'idPunchoutSession' => $punchoutSessionTransfer->getIdPunchoutSession(),
        ]);
    }

    public function logSessionStartFailed(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->error(
            'PunchOut session start failed',
            $sessionStartResponseTransfer->toArray(),
        );
    }

    public function logSessionStarted(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info(
            'PunchOut session start successfully',
            $sessionStartResponseTransfer->toArray(),
        );
    }

    public function logGenericErrorMessage(string $message, array $context = []): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->error(
            $message,
            $context,
        );
    }

    public function logGenericInfoMessage(string $message, array $context = []): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->info(
            $message,
            $context,
        );
    }

    public function logError(string $message, Throwable $throwable): void
    {
        $this->getAuditLogger($this->createAuditLoggerConfigCriteria())->error($message, [
            'exception_message' => $throwable->getMessage(),
            'exception_class' => $throwable::class,
        ]);
    }

    protected function createAuditLoggerConfigCriteria(): AuditLoggerConfigCriteriaTransfer
    {
        $auditLoggerConfigCriteriaTransfer = new AuditLoggerConfigCriteriaTransfer();
        $auditLoggerConfigCriteriaTransfer->setChannelName(static::AUDIT_CHANNEL);

        return $auditLoggerConfigCriteriaTransfer;
    }
}
