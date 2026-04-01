<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionConditionsTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciConfigurationTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class PunchoutOciAuthenticator implements PunchoutOciAuthenticatorInterface
{
    protected const string FAILURE_REASON_NO_CREDENTIAL_FOUND = 'No active credential found for username';

    protected const string FAILURE_REASON_INVALID_PASSWORD = 'Password does not match';

    protected const string FAILURE_REASON_NO_CONNECTION_FOUND = 'No active connection found for credential';

    protected const string FAILURE_REASON_INVALID_REQUEST_URL = 'Request URL does not match connection';

    public function __construct(
        protected PunchoutGatewayRepositoryInterface $punchoutGatewayRepository,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function authenticate(PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer): ?PunchoutConnectionTransfer
    {
        $formData = $ociLoginRequestTransfer->getFormData();
        $username = $formData[PunchoutGatewayConstants::OCI_DEFAULT_USERNAME_FIELD] ?? null;
        $password = $formData[PunchoutGatewayConstants::OCI_DEFAULT_PASSWORD_FIELD] ?? null;

        if ($username === null || $password === null) {
            return null;
        }

        $this->punchoutLogger->logAuthenticationAttempt($username);

        $credentialTransfer = $this->punchoutGatewayRepository->findActiveCredentialByUsername($username);

        if ($credentialTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($username, static::FAILURE_REASON_NO_CREDENTIAL_FOUND);

            return null;
        }

        if (!password_verify($password, $credentialTransfer->getPasswordHashOrFail())) {
            $this->punchoutLogger->logAuthenticationFailure($username, static::FAILURE_REASON_INVALID_PASSWORD);

            return null;
        }

        $connectionTransfer = $this->findActiveConnection($credentialTransfer->getIdPunchoutConnectionOrFail());

        if ($connectionTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($username, static::FAILURE_REASON_NO_CONNECTION_FOUND);

            return null;
        }

        if (!$this->isAllowedRequestUrl($connectionTransfer, $ociLoginRequestTransfer)) {
            $this->punchoutLogger->logRequestUrlFailure($ociLoginRequestTransfer->getRequestUrl(), static::FAILURE_REASON_INVALID_REQUEST_URL);

            return null;
        }

        $connectionTransfer->getOciConfiguration()->setIdCustomer($credentialTransfer->getIdCustomer());

        $this->punchoutLogger->logAuthenticationSuccess($connectionTransfer);

        return $connectionTransfer;
    }

    public function authenticateConnection(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): ?PunchoutConnectionTransfer {
        $formData = $ociLoginRequestTransfer->getFormData();
        $ociConfiguration = $connectionTransfer->getOciConfiguration();

        $usernameField = $ociConfiguration?->getUsernameField() ?? PunchoutGatewayConstants::OCI_DEFAULT_USERNAME_FIELD;
        $passwordField = $ociConfiguration?->getPasswordField() ?? PunchoutGatewayConstants::OCI_DEFAULT_PASSWORD_FIELD;

        $username = $formData[$usernameField] ?? null;
        $password = $formData[$passwordField] ?? null;

        if ($username === null || $password === null) {
            return null;
        }

        $this->punchoutLogger->logAuthenticationAttempt($username);

        $credentialTransfer = $this->punchoutGatewayRepository->findActiveCredentialByUsername($username);

        if ($credentialTransfer === null) {
            $this->punchoutLogger->logAuthenticationFailure($username, static::FAILURE_REASON_NO_CREDENTIAL_FOUND);

            return null;
        }

        if ($credentialTransfer->getIdPunchoutConnectionOrFail() !== $connectionTransfer->getIdPunchoutConnectionOrFail()) {
            $this->punchoutLogger->logAuthenticationFailure($username, static::FAILURE_REASON_NO_CONNECTION_FOUND);

            return null;
        }

        if (!password_verify($password, $credentialTransfer->getPasswordHashOrFail())) {
            $this->punchoutLogger->logAuthenticationFailure($username, static::FAILURE_REASON_INVALID_PASSWORD);

            return null;
        }

        if ($ociConfiguration === null) {
            $connectionTransfer->setOciConfiguration(new PunchoutOciConfigurationTransfer());
        }

        $connectionTransfer->getOciConfiguration()->setIdCustomer($credentialTransfer->getIdCustomer());

        $this->punchoutLogger->logAuthenticationSuccess($connectionTransfer);

        return $connectionTransfer;
    }

    protected function findActiveConnection(int $idPunchoutConnection): ?PunchoutConnectionTransfer
    {
        $conditionsTransfer = new PunchoutConnectionConditionsTransfer();
        $conditionsTransfer->addIdPunchoutConnection($idPunchoutConnection);
        $conditionsTransfer->addProtocolType(PunchoutGatewayConstants::PROTOCOL_TYPE_OCI);
        $conditionsTransfer->setIsActive(true);

        $criteriaTransfer = new PunchoutConnectionCriteriaTransfer();
        $criteriaTransfer->setPunchoutConnectionConditions($conditionsTransfer);

        $connectionCollection = $this->punchoutGatewayRepository->getPunchoutConnectionCollection($criteriaTransfer);

        if ($connectionCollection->getPunchoutConnections()->count() === 0) {
            return null;
        }

        return $connectionCollection->getPunchoutConnections()->offsetGet(0);
    }

    protected function isAllowedRequestUrl(
        PunchoutConnectionTransfer $connectionTransfer,
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
    ): bool {
        $requestUrl = $ociLoginRequestTransfer->getRequestUrl();

        if ($requestUrl === null || $requestUrl === '') {
            return true;
        }

        return str_starts_with($requestUrl, $connectionTransfer->getRequestUrl());
    }
}
