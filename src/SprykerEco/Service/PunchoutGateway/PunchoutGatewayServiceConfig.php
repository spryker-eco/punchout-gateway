<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Service\PunchoutGateway;

use Spryker\Service\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutGatewayServiceConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return list<string>
     */
    public function getExtrinsicBlackList(): array
    {
        return PunchoutGatewayConfig::EXTRINSIC_BLACKLIST;
    }
}
