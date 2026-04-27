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
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutCxmlDemoConnectionCreateConsole extends Console
{
    protected const string COMMAND_NAME = 'punchout-gateway:cxml:demo-connection:create';

    protected const string DESCRIPTION = 'Creates the demo cXML punchout connection entry (store DE).';

    protected const string STORE_NAME = 'DE';

    protected const string CONNECTION_NAME = 'Demo cXML Connection';

    protected const string SENDER_IDENTITY = 'MyNewIdentity';

    protected const string SENDER_SHARED_SECRET = 'jd8je3$ndP';

    protected const string CONFIGURATION_KEY_SENDER_SHARED_SECRET = 'senderSharedSecret';

    protected const bool ALLOW_IFRAME = true;

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)->setDescription(static::DESCRIPTION);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $existingEntity = SpyPunchoutConnectionQuery::create()
            ->filterBySenderIdentity(static::SENDER_IDENTITY)
            ->findOne();

        if ($existingEntity !== null) {
            $output->writeln(sprintf('Connection already exists (id=%d). Nothing to do.', $existingEntity->getIdPunchoutConnection()));

            return static::CODE_SUCCESS;
        }

        $storeTransfer = Locator::getInstance()->store()->facade()->getStoreByName(static::STORE_NAME);

        $entity = new SpyPunchoutConnection();
        $entity->setFkStore($storeTransfer->getIdStoreOrFail());
        $entity->setName(static::CONNECTION_NAME);
        $entity->setIsActive(true);
        $entity->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_CXML);
        $entity->setSenderIdentity(static::SENDER_IDENTITY);
        $entity->setConfiguration((string)json_encode([
            static::CONFIGURATION_KEY_SENDER_SHARED_SECRET => password_hash(static::SENDER_SHARED_SECRET, PASSWORD_DEFAULT),
        ]));
        $entity->setProcessorPluginClass(DefaultCxmlProcessorPlugin::class);
        $entity->setAllowIframe(static::ALLOW_IFRAME);
        $entity->save();

        $output->writeln(sprintf('Created cXML demo connection (id=%d).', $entity->getIdPunchoutConnection()));

        return static::CODE_SUCCESS;
    }
}
