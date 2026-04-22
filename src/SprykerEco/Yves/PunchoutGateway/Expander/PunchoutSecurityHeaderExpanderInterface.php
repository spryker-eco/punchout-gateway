<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
