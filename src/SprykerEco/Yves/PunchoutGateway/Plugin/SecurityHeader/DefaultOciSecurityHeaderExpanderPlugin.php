<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Plugin\SecurityHeader;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerEco\Yves\PunchoutGateway\Dependency\Plugin\PunchoutSecurityHeaderExpanderPluginInterface;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class DefaultOciSecurityHeaderExpanderPlugin extends AbstractPlugin implements PunchoutSecurityHeaderExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<string> $directives
     *
     * @return array<string>
     */
    public function expand(array $directives, PunchoutSessionTransfer $punchoutSession, string $origin): array
    {
        return $this->getFactory()
            ->createOciSecurityHeaderExpander()
            ->expand($directives, $punchoutSession, $origin);
    }
}
