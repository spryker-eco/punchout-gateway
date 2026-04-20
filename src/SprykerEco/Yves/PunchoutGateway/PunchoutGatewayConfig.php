<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Yves\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;
use Symfony\Component\HttpFoundation\Response;

class PunchoutGatewayConfig extends AbstractBundleConfig
{
    /**
     * @api
     */
    public function getYvesBaseUrl(): string
    {
        return $this->get(ApplicationConstants::BASE_URL_YVES);
    }

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
    public function getSuccessResponseHttpCode(): int
    {
        return Response::HTTP_OK;
    }

    /**
     * @api
     */
    public function getErrorResponseHttpCode(): int
    {
        return Response::HTTP_UNAUTHORIZED;
    }
}
