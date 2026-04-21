<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciConfigurationTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class PunchoutOciAuthenticator implements PunchoutOciAuthenticatorInterface
{
    protected const string FAILURE_REASON_NO_CREDENTIAL_FOUND = 'No active credential found for username';

    protected const string FAILURE_REASON_INVALID_PASSWORD = 'Password does not match';

    protected const string FAILURE_REASON_NO_CONNECTION_FOUND = 'No active connection found for credential';

    public function __construct(
        protected PunchoutGatewayRepositoryInterface $punchoutGatewayRepository,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function authenticateConnection(
        PunchoutSetupRequestTransfer $setupRequestTransfer
    ): ?PunchoutConnectionTransfer {
        $connectionTransfer = $setupRequestTransfer->getConnection();

        $formData = $setupRequestTransfer->getOciLoginRequest()->getFormData();
        $ociConfiguration = $connectionTransfer->getOciConfiguration();

        $usernameField = $ociConfiguration?->getUsernameField() ?? PunchoutGatewayConfig::OCI_DEFAULT_USERNAME_FIELD;
        $passwordField = $ociConfiguration?->getPasswordField() ?? PunchoutGatewayConfig::OCI_DEFAULT_PASSWORD_FIELD;

        $username = $formData[$usernameField] ?? null;
        $password = $formData[$passwordField] ?? null;

        if ($username === null || $password === null) {
            if ($password !== null) {
                $formData[$passwordField] = $this->maskPassword($password);
            }

            $this->punchoutLogger->logGenericInfoMessage(sprintf('Username (field %s) and/or password(field %s) are empty', $usernameField, $passwordField), $formData);

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

        $connectionTransfer->setIdCustomer($credentialTransfer->getIdCustomer());

        $this->punchoutLogger->logAuthenticationSuccess($connectionTransfer);

        return $connectionTransfer;
    }

    protected function maskPassword(string $password): string
    {
        return sprintf('%s***%s', substr($password, 0, 2), substr($password, -2));
    }
}
