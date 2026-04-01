<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\PunchoutGateway;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Yves\Kernel\AbstractBundleConfig;
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
        return true;
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
