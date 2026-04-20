<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;
use SprykerEco\Yves\PunchoutGateway\Plugin\Form\DefaultCxmlPunchoutFormHandlerPlugin;
use SprykerEco\Yves\PunchoutGateway\Plugin\Form\DefaultOciPunchoutFormHandlerPlugin;
use SprykerEco\Yves\PunchoutGateway\Plugin\SecurityHeader\DefaultOciSecurityHeaderExpanderPlugin;

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

    public const string SERVICE_PUNCHOUT_GATEWAY = 'SERVICE_PUNCHOUT_GATEWAY';

    public const string PLUGINS_PUNCHOUT_SECURITY_HEADER_EXPANDER = 'PLUGINS_PUNCHOUT_SECURITY_HEADER_EXPANDER';

    public const string PLUGINS_PUNCHOUT_FORM_HANDLER = 'PLUGINS_PUNCHOUT_FORM_HANDLER';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addPunchoutGatewayClient($container);
        $container = $this->addCustomerClient($container);
        $container = $this->addSessionClient($container);
        $container = $this->addSecurityTokenStorage($container);
        $container = $this->addQuoteClient($container);
        $container = $this->addPunchoutGatewayService($container);
        $container = $this->addPunchoutSecurityHeaderExpanderPlugins($container);
        $container = $this->addPunchoutFormHandlerPlugins($container);

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

    protected function addPunchoutGatewayService(Container $container): Container
    {
        $container->set(static::SERVICE_PUNCHOUT_GATEWAY, function (Container $container) {
            return $container->getLocator()->punchoutGateway()->service();
        });

        return $container;
    }

    protected function addPunchoutSecurityHeaderExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_PUNCHOUT_SECURITY_HEADER_EXPANDER, function (): array {
            return $this->getPunchoutSecurityHeaderExpanderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerEco\Yves\PunchoutGateway\Dependency\Plugin\PunchoutSecurityHeaderExpanderPluginInterface>
     */
    protected function getPunchoutSecurityHeaderExpanderPlugins(): array
    {
        return [
            new DefaultOciSecurityHeaderExpanderPlugin(),
        ];
    }

    protected function addPunchoutFormHandlerPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_PUNCHOUT_FORM_HANDLER, function (): array {
            return $this->getPunchoutFormHandlerPlugins();
        });

        return $container;
    }

    /**
     * @return array<\SprykerEco\Yves\PunchoutGateway\Plugin\Form\PunchoutFormHandlerPluginInterface>
     */
    protected function getPunchoutFormHandlerPlugins(): array
    {
        return [
            new DefaultCxmlPunchoutFormHandlerPlugin(),
            new DefaultOciPunchoutFormHandlerPlugin(),
        ];
    }
}
