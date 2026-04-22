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
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;

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
        );
    }

    public function createPunchoutLogger(): PunchoutLoggerInterface
    {
        return new PunchoutLogger();
    }
}
