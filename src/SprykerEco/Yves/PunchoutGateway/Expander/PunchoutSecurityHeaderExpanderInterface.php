<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Expander;

interface PunchoutSecurityHeaderExpanderInterface
{
    /**
     * Specification:
     * - Reads the precomputed CSP fragment from the HTTP session.
     * - Merges the fragment's directives into the existing Content-Security-Policy header.
     * - Returns the headers untouched when no fragment is stored in the session.
     *
     * @param array<string, string> $securityHeaders
     *
     * @return array<string, string>
     */
    public function expand(array $securityHeaders): array;
}
