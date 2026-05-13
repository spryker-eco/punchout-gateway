<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Service\PunchoutGateway;

use Spryker\Service\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;

class PunchoutGatewayConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return list<string>
     */
    public function getExtrinsicBlackList(): array
    {
        return SharedPunchoutGatewayConfig::EXTRINSIC_BLACKLIST;
    }
}
