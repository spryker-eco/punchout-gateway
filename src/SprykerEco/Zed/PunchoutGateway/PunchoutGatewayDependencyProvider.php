<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 */
class PunchoutGatewayDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_QUOTE = 'FACADE_QUOTE';

    public const string FACADE_STORE = 'FACADE_STORE';

    public const string FACADE_CUSTOMER = 'FACADE_CUSTOMER';

    public const string PLUGINS_PUNCHOUT_SESSION_IN_QUOTE_EXPANDER = 'PLUGINS_PUNCHOUT_SESSION_IN_QUOTE_EXPANDER';

    public const string SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    public const string SERVICE_PUNCHOUT_GATEWAY = 'SERVICE_PUNCHOUT_GATEWAY';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addPunchoutSessionInQuoteExpanderPlugins($container);
        $container = $this->addQuoteFacade($container);
        $container = $this->addStoreFacade($container);
        $container = $this->addCustomerFacade($container);
        $container = $this->addPunchoutGatewayService($container);

        return $container;
    }

    public function providePersistenceLayerDependencies(Container $container): Container
    {
        $container = parent::providePersistenceLayerDependencies($container);
        $container = $this->addUtilEncodingService($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addQuoteFacade($container);

        return $container;
    }

    protected function addPunchoutSessionInQuoteExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_PUNCHOUT_SESSION_IN_QUOTE_EXPANDER, function (): array {
            return $this->getPunchoutSessionInQuoteExpanderPlugins();
        });

        return $container;
    }

    protected function addQuoteFacade(Container $container): Container
    {
        $container->set(static::FACADE_QUOTE, function (Container $container) {
            return $container->getLocator()->quote()->facade();
        });

        return $container;
    }

    protected function addStoreFacade(Container $container): Container
    {
        $container->set(static::FACADE_STORE, function (Container $container) {
            return $container->getLocator()->store()->facade();
        });

        return $container;
    }

    protected function addCustomerFacade(Container $container): Container
    {
        $container->set(static::FACADE_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->facade();
        });

        return $container;
    }

    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container) {
            return $container->getLocator()->utilEncoding()->service();
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

    /**
     * @return array<\SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutSessionInQuoteExpanderPluginInterface>
     */
    protected function getPunchoutSessionInQuoteExpanderPlugins(): array
    {
        return [];
    }
}
