<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Persistence\Mapper;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlConfigurationTransfer;
use Generated\Shared\Transfer\PunchoutOciConfigurationTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection;
use Propel\Runtime\Collection\ObjectCollection;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

class PunchoutConnectionMapper
{
    protected const string CONFIGURATION_KEY_SENDER_SHARED_SECRET = 'senderSharedSecret';

    protected const string CONFIGURATION_KEY_FORM_METHOD = 'formMethod';

    protected const string CONFIGURATION_KEY_USERNAME = 'username';

    protected const string CONFIGURATION_KEY_PASSWORD = 'password';

    protected const string CONFIGURATION_KEY_USERNAME_FIELD = 'usernameField';

    protected const string CONFIGURATION_KEY_PASSWORD_FIELD = 'passwordField';

    /**
     * @param \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection> $punchoutConnectionEntities
     */
    public function mapPunchoutConnectionEntitiesToPunchoutConnectionCollectionTransfer(
        ObjectCollection $punchoutConnectionEntities,
        PunchoutConnectionCollectionTransfer $punchoutConnectionCollectionTransfer,
    ): PunchoutConnectionCollectionTransfer {
        foreach ($punchoutConnectionEntities as $punchoutConnectionEntity) {
            $punchoutConnectionCollectionTransfer->addPunchoutConnection(
                $this->mapPunchoutConnectionEntityToTransfer($punchoutConnectionEntity, new PunchoutConnectionTransfer()),
            );
        }

        return $punchoutConnectionCollectionTransfer;
    }

    public function mapPunchoutConnectionEntityToTransfer(
        ?SpyPunchoutConnection $punchoutConnectionEntity,
        PunchoutConnectionTransfer $punchoutConnectionTransfer,
    ): ?PunchoutConnectionTransfer {
        if (!$punchoutConnectionEntity) {
            return null;
        }

        $punchoutConnectionTransfer->fromArray($punchoutConnectionEntity->toArray(), true);
        $punchoutConnectionTransfer->setIdStore($punchoutConnectionEntity->getFkStore());

        $protocolConfiguration = $this->decodeProtocolConfiguration(
            $punchoutConnectionEntity->getConfiguration(),
        );

        if ($punchoutConnectionEntity->getProtocolType() === PunchoutGatewayConstants::PROTOCOL_TYPE_CXML) {
            $punchoutConnectionTransfer->setCxmlConfiguration(
                $this->mapCxmlConfiguration($protocolConfiguration, $punchoutConnectionEntity),
            );
        }

        if ($punchoutConnectionEntity->getProtocolType() === PunchoutGatewayConstants::PROTOCOL_TYPE_OCI) {
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
        $ociConfigurationTransfer->setUsername($protocolConfiguration[static::CONFIGURATION_KEY_USERNAME] ?? null);
        $ociConfigurationTransfer->setPassword($protocolConfiguration[static::CONFIGURATION_KEY_PASSWORD] ?? null);
        $ociConfigurationTransfer->setUsernameField($protocolConfiguration[static::CONFIGURATION_KEY_USERNAME_FIELD] ?? null);
        $ociConfigurationTransfer->setPasswordField($protocolConfiguration[static::CONFIGURATION_KEY_PASSWORD_FIELD] ?? null);

        return $ociConfigurationTransfer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeProtocolConfiguration(?string $protocolConfiguration): array
    {
        if ($protocolConfiguration === null || $protocolConfiguration === '') {
            return [];
        }

        return json_decode($protocolConfiguration, true) ?: [];
    }
}
