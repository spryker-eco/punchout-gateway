<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence\Mapper;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlConfigurationTransfer;
use Generated\Shared\Transfer\PunchoutOciConfigurationTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutConnectionMapper
{
    public function __construct(protected UtilEncodingServiceInterface $utilEncodingService)
    {
    }

    protected const string CONFIGURATION_KEY_SENDER_SHARED_SECRET = 'senderSharedSecret';

    protected const string CONFIGURATION_KEY_FORM_METHOD = 'formMethod';

    protected const string CONFIGURATION_KEY_USERNAME_FIELD = 'usernameField';

    protected const string CONFIGURATION_KEY_PASSWORD_FIELD = 'passwordField';

    public function mapPunchoutConnectionEntityToTransfer(
        ?SpyPunchoutConnection $punchoutConnectionEntity,
        PunchoutConnectionTransfer $punchoutConnectionTransfer,
    ): ?PunchoutConnectionTransfer {
        if (!$punchoutConnectionEntity) {
            return null;
        }

        $punchoutConnectionTransfer->fromArray($punchoutConnectionEntity->toArray(), true);
        $punchoutConnectionTransfer->setIdStore($punchoutConnectionEntity->getFkStore());
        $punchoutConnectionTransfer->setStoreName($punchoutConnectionEntity->getSpyStore()->getName());

        $protocolConfiguration = $this->decodeProtocolConfiguration(
            $punchoutConnectionEntity->getConfiguration(),
        );

        if ($punchoutConnectionEntity->getProtocolType() === PunchoutGatewayConfig::PROTOCOL_TYPE_CXML) {
            $punchoutConnectionTransfer->setCxmlConfiguration(
                $this->mapCxmlConfiguration($protocolConfiguration, $punchoutConnectionEntity),
            );
        }

        if ($punchoutConnectionEntity->getProtocolType() === PunchoutGatewayConfig::PROTOCOL_TYPE_OCI) {
            $punchoutConnectionTransfer->setOciConfiguration(
                $this->mapOciConfiguration($protocolConfiguration),
            );
        }

        return $punchoutConnectionTransfer;
    }

    /**
     * @param array<string, mixed> $protocolConfiguration
     */
    protected function mapCxmlConfiguration(
        array $protocolConfiguration,
        SpyPunchoutConnection $punchoutConnectionEntity,
    ): PunchoutCxmlConfigurationTransfer {
        $cxmlConfigurationTransfer = new PunchoutCxmlConfigurationTransfer();

        $cxmlConfigurationTransfer->setSenderIdentity($punchoutConnectionEntity->getSenderIdentity());
        $cxmlConfigurationTransfer->setSenderSharedSecret($protocolConfiguration[static::CONFIGURATION_KEY_SENDER_SHARED_SECRET] ?? null);

        return $cxmlConfigurationTransfer;
    }

    /**
     * @param array<string, mixed> $protocolConfiguration
     */
    protected function mapOciConfiguration(array $protocolConfiguration): PunchoutOciConfigurationTransfer
    {
        $ociConfigurationTransfer = new PunchoutOciConfigurationTransfer();
        $ociConfigurationTransfer->setFormMethod($protocolConfiguration[static::CONFIGURATION_KEY_FORM_METHOD] ?? null);
        $ociConfigurationTransfer->setUsernameField($protocolConfiguration[static::CONFIGURATION_KEY_USERNAME_FIELD] ?? null);
        $ociConfigurationTransfer->setPasswordField($protocolConfiguration[static::CONFIGURATION_KEY_PASSWORD_FIELD] ?? null);

        return $ociConfigurationTransfer;
    }

    public function mapPunchoutConnectionTransferToEntity(
        PunchoutConnectionTransfer $punchoutConnectionTransfer,
        SpyPunchoutConnection $punchoutConnectionEntity,
    ): SpyPunchoutConnection {
        $punchoutConnectionEntity->setFkStore($punchoutConnectionTransfer->getIdStoreOrFail());
        $punchoutConnectionEntity->setName($punchoutConnectionTransfer->getNameOrFail());
        $punchoutConnectionEntity->setProtocolType($punchoutConnectionTransfer->getProtocolTypeOrFail());
        $punchoutConnectionEntity->setIsActive((bool)$punchoutConnectionTransfer->getIsActive());
        $punchoutConnectionEntity->setAllowIframe((bool)$punchoutConnectionTransfer->getAllowIframe());
        $punchoutConnectionEntity->setRequestUrl($punchoutConnectionTransfer->getRequestUrl());
        $punchoutConnectionEntity->setProcessorPluginClass($punchoutConnectionTransfer->getProcessorPluginClass());

        if ($punchoutConnectionTransfer->getProtocolType() === PunchoutGatewayConfig::PROTOCOL_TYPE_CXML) {
            $this->applyCxmlConfiguration($punchoutConnectionTransfer, $punchoutConnectionEntity);
        }

        if ($punchoutConnectionTransfer->getProtocolType() === PunchoutGatewayConfig::PROTOCOL_TYPE_OCI) {
            $this->applyOciConfiguration($punchoutConnectionTransfer, $punchoutConnectionEntity);
        }

        return $punchoutConnectionEntity;
    }

    protected function applyCxmlConfiguration(
        PunchoutConnectionTransfer $punchoutConnectionTransfer,
        SpyPunchoutConnection $punchoutConnectionEntity,
    ): void {
        $cxmlConfiguration = $punchoutConnectionTransfer->getCxmlConfiguration();

        if ($cxmlConfiguration === null) {
            return;
        }

        $punchoutConnectionEntity->setSenderIdentity($cxmlConfiguration->getSenderIdentity());

        $configuration = [];

        if ($cxmlConfiguration->getSenderSharedSecret() !== null) {
            $configuration[static::CONFIGURATION_KEY_SENDER_SHARED_SECRET] = password_hash(
                $cxmlConfiguration->getSenderSharedSecret(),
                PASSWORD_DEFAULT,
            );
        }

        $punchoutConnectionEntity->setConfiguration(
            $this->utilEncodingService->encodeJson($configuration) ?? '{}',
        );
    }

    protected function applyOciConfiguration(
        PunchoutConnectionTransfer $punchoutConnectionTransfer,
        SpyPunchoutConnection $punchoutConnectionEntity,
    ): void {
        $ociConfiguration = $punchoutConnectionTransfer->getOciConfiguration();

        if ($ociConfiguration === null) {
            return;
        }

        $configuration = [];

        if ($ociConfiguration->getFormMethod() !== null) {
            $configuration[static::CONFIGURATION_KEY_FORM_METHOD] = $ociConfiguration->getFormMethod();
        }

        if ($ociConfiguration->getUsernameField() !== null) {
            $configuration[static::CONFIGURATION_KEY_USERNAME_FIELD] = $ociConfiguration->getUsernameField();
        }

        if ($ociConfiguration->getPasswordField() !== null) {
            $configuration[static::CONFIGURATION_KEY_PASSWORD_FIELD] = $ociConfiguration->getPasswordField();
        }

        $punchoutConnectionEntity->setConfiguration(
            $this->utilEncodingService->encodeJson($configuration) ?? '{}',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeProtocolConfiguration(?string $protocolConfiguration): array
    {
        if ($protocolConfiguration === null || $protocolConfiguration === '') {
            return [];
        }

        return $this->utilEncodingService->decodeJson($protocolConfiguration, true) ?: [];
    }
}
