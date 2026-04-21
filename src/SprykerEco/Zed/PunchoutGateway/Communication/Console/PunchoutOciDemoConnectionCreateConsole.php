<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\PunchoutGateway\Communication\Console;

use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnectionQuery;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Spryker\Zed\Kernel\Locator;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PunchoutOciDemoConnectionCreateConsole extends Console
{
    protected const string COMMAND_NAME = 'punchout-gateway:oci:demo-connection:create';
    protected const string DESCRIPTION = 'Creates the demo OCI punchout connection entry (store DE).';

    protected const string STORE_NAME = 'DE';
    protected const string CONNECTION_NAME = 'Demo OCI Connection';
    protected const string REQUEST_URL = '/punchout-gateway/oci/demo';
    protected const string OCI_FORM_METHOD = 'POST';
    protected const string OCI_USERNAME_FIELD = 'username';
    protected const string OCI_PASSWORD_FIELD = 'password';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)->setDescription(static::DESCRIPTION);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeTransfer = Locator::getInstance()->store()->facade()->getStoreByName(static::STORE_NAME);

        $existingEntity = SpyPunchoutConnectionQuery::create()
            ->filterByFkStore($storeTransfer->getIdStoreOrFail())
            ->filterByRequestUrl(static::REQUEST_URL)
            ->findOne();

        if ($existingEntity !== null) {
            $output->writeln(sprintf('Connection already exists (id=%d). Nothing to do.', $existingEntity->getIdPunchoutConnection()));

            return static::CODE_SUCCESS;
        }

        $entity = new SpyPunchoutConnection();
        $entity->setFkStore($storeTransfer->getIdStoreOrFail());
        $entity->setName(static::CONNECTION_NAME);
        $entity->setIsActive(true);
        $entity->setAllowIframe(true);
        $entity->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_OCI);
        $entity->setRequestUrl(static::REQUEST_URL);

        $configuration = [];

        if (static::OCI_USERNAME_FIELD !== PunchoutGatewayConfig::OCI_DEFAULT_USERNAME_FIELD) {
            $configuration['usernameField'] = static::OCI_USERNAME_FIELD;
        }

        if (static::OCI_PASSWORD_FIELD !== PunchoutGatewayConfig::OCI_DEFAULT_PASSWORD_FIELD) {
            $configuration['passwordField'] = static::OCI_PASSWORD_FIELD;
        }

        if (static::OCI_FORM_METHOD !== 'POST') {
            $configuration[\Generated\Shared\Transfer\PunchoutOciConfigurationTransfer::FORM_METHOD] = static::OCI_FORM_METHOD;
        }

        if ($configuration) {
            $entity->setConfiguration((string)json_encode($configuration));
        }
        $entity->setProcessorPluginClass(DefaultOciProcessorPlugin::class);
        $entity->save();

        $output->writeln(sprintf('Created OCI demo connection (id=%d).', $entity->getIdPunchoutConnection()));

        return static::CODE_SUCCESS;
    }
}
