<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Dependency\Plugin;

use Generated\Shared\Transfer\PunchoutSessionTransfer;

/**
 * Enhances Content-Security-Policy directives for protocol-specific punchout form submission at session start.
 *
 * @api
 */
interface PunchoutSecurityHeaderExpanderPluginInterface
{
    /**
     * Specification:
     * - Appends protocol-specific CSP directive strings to the given list.
     * - Must not add duplicate directives.
     *
     * @api
     *
     * @param array<string> $directives
     *
     * @return array<string>
     */
    public function expand(array $directives, PunchoutSessionTransfer $punchoutSession, string $origin): array;
}
