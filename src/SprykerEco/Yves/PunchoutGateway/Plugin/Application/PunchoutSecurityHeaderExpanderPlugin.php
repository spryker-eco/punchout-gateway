<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Plugin\Application;

use Spryker\Yves\ApplicationExtension\Dependency\Plugin\SecurityHeaderExpanderPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class PunchoutSecurityHeaderExpanderPlugin extends AbstractPlugin implements SecurityHeaderExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Reads a precomputed CSP fragment from the HTTP session and merges it into `Content-Security-Policy`.
     * - No-op when no fragment is stored in the session (i.e. no punchout session has been started).
     *
     * @api
     *
     * @param array<string, string> $securityHeaders
     *
     * @return array<string, string>
     */
    public function expand(array $securityHeaders): array
    {
        return $this->getFactory()->createPunchoutSecurityHeaderExpander()->expand($securityHeaders);
    }
}
