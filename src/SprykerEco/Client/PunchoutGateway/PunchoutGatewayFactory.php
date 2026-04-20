<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
