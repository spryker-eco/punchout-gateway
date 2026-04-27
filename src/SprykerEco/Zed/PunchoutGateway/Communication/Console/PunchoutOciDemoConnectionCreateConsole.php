<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\PunchoutGateway\Communication\Console;

use Generated\Shared\Transfer\PunchoutOciConfigurationTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnectionQuery;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutCredential;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Spryker\Zed\Kernel\Locator;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutOciDemoConnectionCreateConsole extends Console
{
    protected const string COMMAND_NAME = 'punchout-gateway:oci:demo-connection:create';

    protected const string DESCRIPTION = 'Creates the demo OCI punchout connection entry (store DE).';

    protected const string STORE_NAME = 'DE';

    protected const string CONNECTION_NAME = 'Demo OCI Connection';

    protected const string REQUEST_URL = '/punchout-gateway/oci/demo1';

    protected const string OCI_FORM_METHOD = 'POST';

    protected const string OCI_USERNAME_FIELD = 'USERNAME';

    protected const string OCI_PASSWORD_FIELD = 'PASSWORD';

    protected const string OCI_USERNAME = 'username';

    protected const string OCI_PASSWORD = 'password';

    protected const int ID_CUSTOMER = 3;

    protected const bool ALLOW_IFRAME = true;

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

        $punchoutConnectionEntity = new SpyPunchoutConnection();
        $punchoutConnectionEntity->setFkStore($storeTransfer->getIdStoreOrFail());
        $punchoutConnectionEntity->setName(static::CONNECTION_NAME);
        $punchoutConnectionEntity->setIsActive(true);
        $punchoutConnectionEntity->setAllowIframe(true);
        $punchoutConnectionEntity->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_OCI);
        $punchoutConnectionEntity->setRequestUrl(static::REQUEST_URL);
        $punchoutConnectionEntity->setAllowIframe(static::ALLOW_IFRAME);

        $configuration = [];

        if (static::OCI_USERNAME_FIELD !== PunchoutGatewayConfig::OCI_DEFAULT_USERNAME_FIELD) {
            $configuration['usernameField'] = static::OCI_USERNAME_FIELD;
        }

        if (static::OCI_PASSWORD_FIELD !== PunchoutGatewayConfig::OCI_DEFAULT_PASSWORD_FIELD) {
            $configuration['passwordField'] = static::OCI_PASSWORD_FIELD;
        }

        if (static::OCI_FORM_METHOD !== 'POST') {
            $configuration[PunchoutOciConfigurationTransfer::FORM_METHOD] = static::OCI_FORM_METHOD;
        }

        if ($configuration) {
            $punchoutConnectionEntity->setConfiguration((string)json_encode($configuration));
        }
        $punchoutConnectionEntity->setProcessorPluginClass(DefaultOciProcessorPlugin::class);
        $punchoutConnectionEntity->save();

        $output->writeln(sprintf('Created OCI demo connection (id=%d).', $punchoutConnectionEntity->getIdPunchoutConnection()));

        $pc = new SpyPunchoutCredential();
        $pc->setFkPunchoutConnection($punchoutConnectionEntity->getIdPunchoutConnection());
        $pc->setUsername(static::OCI_USERNAME);
        $pc->setPasswordHash(password_hash(static::OCI_PASSWORD, PASSWORD_DEFAULT));
        $pc->setFkCustomer(static::ID_CUSTOMER);
        $pc->save();

        $output->writeln(sprintf('Created credentials (id=%d).', $pc->getIdPunchoutCredential()));

        return static::CODE_SUCCESS;
    }
}
