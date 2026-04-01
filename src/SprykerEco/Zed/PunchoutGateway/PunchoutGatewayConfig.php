<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class PunchoutGatewayConfig extends AbstractBundleConfig
{
    /**
     * @api
     */
    public function isLoggingEnabled(): bool
    {
        return true;
    }

    /**
     * @api
     */
    public function getCxmlSessionStartUrlSeconds(): int
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
    public function isCxmlSessionDeletedOnStart(): bool
    {
        return false;
    }
}
