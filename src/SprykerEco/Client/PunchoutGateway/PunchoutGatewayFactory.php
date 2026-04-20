<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Client\PunchoutGateway;

use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use SprykerEco\Client\PunchoutGateway\Zed\PunchoutGatewayStub;
use SprykerEco\Client\PunchoutGateway\Zed\PunchoutGatewayStubInterface;

class PunchoutGatewayFactory extends AbstractFactory
{
    public function createPunchoutGatewayStub(): PunchoutGatewayStubInterface
    {
        return new PunchoutGatewayStub(
            $this->getZedRequestClient(),
        );
    }

    public function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::CLIENT_ZED_REQUEST);
    }
}
