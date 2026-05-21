<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway;

use Spryker\Service\Kernel\AbstractBundleDependencyProvider;
use Spryker\Service\Kernel\Container;
use SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper\ItemTransferFieldMapperPlugin;
use SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper\QuoteTransferFieldMapperPlugin;

/**
 * @method \SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig getConfig()
 */
class PunchoutGatewayDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string PLUGINS_FIELD_MAPPER = 'PLUGINS_FIELD_MAPPER';

    public function provideServiceDependencies(Container $container): Container
    {
        $container = parent::provideServiceDependencies($container);
        $container = $this->addFieldMapperPlugins($container);

        return $container;
    }

    protected function addFieldMapperPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_FIELD_MAPPER, function (): array {
            return $this->getFieldMapperPlugins();
        });

        return $container;
    }

    /**
     * @return array<string, \SprykerEco\Service\PunchoutGateway\Dependency\Plugin\PunchoutFieldMapperPluginInterface>
     */
    protected function getFieldMapperPlugins(): array
    {
        return [
            'item' => new ItemTransferFieldMapperPlugin(),
            'quote' => new QuoteTransferFieldMapperPlugin(),
        ];
    }
}
