<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form\DataProvider;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutConnectionFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCxmlConfigurationFormType;

class PunchoutConnectionFormDataProvider
{
    /**
     * @param array<string, string> $processorPlugins
     */
    public function __construct(
        protected StoreFacadeInterface $storeFacade,
        protected array $processorPlugins
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(?PunchoutConnectionTransfer $punchoutConnectionTransfer = null): array
    {
        if ($punchoutConnectionTransfer === null) {
            return [];
        }

        $punchoutConnectionTransfer = clone $punchoutConnectionTransfer;

        $data = $punchoutConnectionTransfer->toArray(true, true);

        if ($punchoutConnectionTransfer->getProtocolType() === PunchoutGatewayConfig::PROTOCOL_TYPE_CXML) {
            $punchoutConnectionTransfer->getCxmlConfiguration()?->setSenderSharedSecret('');
        }

        if ($punchoutConnectionTransfer->getProtocolType() === PunchoutGatewayConfig::PROTOCOL_TYPE_OCI) {
            $data[PunchoutConnectionTransfer::REQUEST_URL] = str_replace(
                PunchoutGatewayConfig::OCI_URL_PREFIX,
                '',
                $data[PunchoutConnectionTransfer::REQUEST_URL],
            );
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(?PunchoutConnectionTransfer $punchoutConnectionTransfer = null): array
    {
        $choices = [];
        $typeMap = [];

        foreach ($this->processorPlugins as $title => $plugin) {
            $fqcn = $plugin;
            $choices[$title] = $fqcn;
            $typeMap[$fqcn] = (new $plugin())->getType();
        }

        return [
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_CHOICES => $choices,
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_TYPE_MAP => $typeMap,
            PunchoutConnectionFormType::OPTION_STORE_CHOICES => $this->getStoreChoices(),
            PunchoutConnectionFormType::OPTION_PROTOCOL_TYPE_CHOICES => [
                'Choice cXML' => PunchoutGatewayConfig::PROTOCOL_TYPE_CXML,
                'Choice OCI' => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
            ],
            PunchoutCxmlConfigurationFormType::OPTION_IS_CREATE => $punchoutConnectionTransfer?->getIdPunchoutConnection() === null,
            PunchoutConnectionFormType::OPTION_ID_PUNCHOUT_CONNECTION => $punchoutConnectionTransfer?->getIdPunchoutConnection(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function getStoreChoices(): array
    {
        $stores = $this->storeFacade->getAllStores();

        $choices = [];

        foreach ($stores as $storeTransfer) {
            $choices[$storeTransfer->getNameOrFail()] = $storeTransfer->getIdStoreOrFail();
        }

        return $choices;
    }
}
