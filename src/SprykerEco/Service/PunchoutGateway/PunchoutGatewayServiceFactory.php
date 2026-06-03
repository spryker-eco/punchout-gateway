<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway;

use CXml\Serializer;
use Spryker\Service\Kernel\AbstractServiceFactory;
use SprykerEco\Service\PunchoutGateway\Builder\CxmlBuilder;
use SprykerEco\Service\PunchoutGateway\Builder\CxmlBuilderInterface;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoder;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoderInterface;
use SprykerEco\Service\PunchoutGateway\Mapper\CxmlPunchoutOrderMessageMapper;
use SprykerEco\Service\PunchoutGateway\Mapper\CxmlPunchoutOrderMessageMapperInterface;
use SprykerEco\Service\PunchoutGateway\Mapper\OciFormDataMapper;
use SprykerEco\Service\PunchoutGateway\Mapper\OciFormDataMapperInterface;
use SprykerEco\Service\PunchoutGateway\Mapper\Resolver\FieldSuggestionCollector;
use SprykerEco\Service\PunchoutGateway\Mapper\Resolver\FieldValueResolver;
use SprykerEco\Service\PunchoutGateway\Mapper\Resolver\FieldValueResolverInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;

/**
 * @method \SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig getConfig()
 */
class PunchoutGatewayServiceFactory extends AbstractServiceFactory
{
    public function createCxmlEncoder(): CxmlEncoderInterface
    {
        return new CxmlEncoder(
            $this->createCxmlSerializer(),
        );
    }

    public function createCxmlSerializer(): Serializer
    {
        return Serializer::create();
    }

    public function createCxmlBuilder(): CxmlBuilderInterface
    {
        return new CxmlBuilder();
    }

    public function createCxmlPunchoutOrderMessageMapper(): CxmlPunchoutOrderMessageMapperInterface
    {
        return new CxmlPunchoutOrderMessageMapper(
            $this->createCxmlEncoder(),
            $this->createPunchoutLogger(),
            $this->getConfig(),
            $this->createFieldValueResolver(),
        );
    }

    public function createOciFormDataMapper(): OciFormDataMapperInterface
    {
        return new OciFormDataMapper(
            $this->getConfig(),
            $this->createFieldValueResolver(),
        );
    }

    public function createFieldValueResolver(): FieldValueResolverInterface
    {
        return new FieldValueResolver(
            $this->getFiledMapperPlugins(),
            $this->createPunchoutLogger(),
        );
    }

    /**
     * @return array<string, \SprykerEco\Service\PunchoutGateway\Dependency\Plugin\PunchoutFieldMapperPluginInterface>
     */
    public function getFiledMapperPlugins(): array
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::PLUGINS_FIELD_MAPPER);
    }

    public function createFieldSuggestionCollector(): FieldSuggestionCollector
    {
        return new FieldSuggestionCollector(
            $this->getFiledMapperPlugins(),
        );
    }

    public function createPunchoutLogger(): PunchoutLoggerInterface
    {
        return new PunchoutLogger();
    }
}
