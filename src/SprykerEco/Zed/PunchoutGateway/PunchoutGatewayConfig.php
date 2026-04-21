<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway;

use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

class PunchoutGatewayConfig extends AbstractBundleConfig
{
    /**
     * @api
     */
    public function isLoggingEnabled(): bool
    {
        return (bool)$this->get(PunchoutGatewayConstants::ENABLE_LOGGING, false);
    }

    /**
     * @api
     */
    public function getCxmlSessionStartUrlValidityInSeconds(): int
    {
        return 10 * 60;
    }

    /**
     * @api
     */
    public function getOciDefaultStartUrl(): string
    {
        return '/';
    }

    /**
     * @api
     */
    public function getCxmlSessionTokenLength(): int
    {
        return 32;
    }
}
