<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
        return (bool)$this->get(PunchoutGatewayConstants::ENABLE_LOGGING, false);
    }

    /**
     * @api
     */
    public function getErrorResponseHttpCode(): int
    {
        return Response::HTTP_UNAUTHORIZED;
    }
}
