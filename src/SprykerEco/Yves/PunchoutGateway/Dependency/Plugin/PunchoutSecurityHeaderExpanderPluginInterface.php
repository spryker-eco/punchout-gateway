<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
     * - Returns true when this plugin handles the punchout session's protocol.
     *
     * @api
     */
    public function isApplicable(PunchoutSessionTransfer $punchoutSession): bool;

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
