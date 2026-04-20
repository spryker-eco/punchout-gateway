<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
        return (bool)$this->get(PunchoutGatewayConstants::ENABLE_LOGGING, true);
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
