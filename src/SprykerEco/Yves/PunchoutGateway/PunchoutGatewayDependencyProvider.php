<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\PunchoutGateway;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayConfig getConfig()
 */
class PunchoutGatewayDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @uses \Spryker\Yves\Security\Plugin\Application\SecurityApplicationPlugin::SERVICE_SECURITY_TOKEN_STORAGE
     */
    public const string SERVICE_SECURITY_TOKEN_STORAGE = 'security.token_storage';

    public const string CLIENT_PUNCHOUT_GATEWAY = 'CLIENT_PUNCHOUT_GATEWAY';

    public const string CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public const string CLIENT_SESSION = 'CLIENT_SESSION';

    public const string CLIENT_QUOTE = 'CLIENT_QUOTE';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addPunchoutGatewayClient($container);
        $container = $this->addCustomerClient($container);
        $container = $this->addSessionClient($container);
        $container = $this->addSecurityTokenStorage($container);
        $container = $this->addQuoteClient($container);

        return $container;
    }

    protected function addPunchoutGatewayClient(Container $container): Container
    {
        $container->set(static::CLIENT_PUNCHOUT_GATEWAY, function (Container $container) {
            return $container->getLocator()->punchoutGateway()->client();
        });

        return $container;
    }

    protected function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->client();
        });

        return $container;
    }

    protected function addSessionClient(Container $container): Container
    {
        $container->set(static::CLIENT_SESSION, function (Container $container) {
            return $container->getLocator()->session()->client();
        });

        return $container;
    }

    protected function addSecurityTokenStorage(Container $container): Container
    {
        $container->set(static::SERVICE_SECURITY_TOKEN_STORAGE, function (Container $container) {
            return $container->getApplicationService(static::SERVICE_SECURITY_TOKEN_STORAGE);
        });

        return $container;
    }

    protected function addQuoteClient(Container $container): Container
    {
        $container->set(static::CLIENT_QUOTE, function (Container $container) {
            return $container->getLocator()->quote()->client();
        });

        return $container;
    }
}
