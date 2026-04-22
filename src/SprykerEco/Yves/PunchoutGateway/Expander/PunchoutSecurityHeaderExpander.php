<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Expander;

use Spryker\Client\Session\SessionClientInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutSecurityHeaderExpander implements PunchoutSecurityHeaderExpanderInterface
{
    protected const string HEADER_CONTENT_SECURITY_POLICY = 'Content-Security-Policy';

    public function __construct(protected SessionClientInterface $sessionClient)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function expand(array $securityHeaders): array
    {
        $fragment = $this->sessionClient->get(PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT);

        if (!$fragment) {
            return $securityHeaders;
        }

        if (!isset($securityHeaders[static::HEADER_CONTENT_SECURITY_POLICY])) {
            $securityHeaders[static::HEADER_CONTENT_SECURITY_POLICY] = $fragment;

            return $securityHeaders;
        }

        foreach ($this->parseFragment($fragment) as $directive => $origins) {
            foreach ($origins as $origin) {
                $securityHeaders = $this->appendToDirective($securityHeaders, $directive, $origin);
            }
        }

        return $securityHeaders;
    }

    /**
     * @return array<string, array<string>>
     */
    protected function parseFragment(string $fragment): array
    {
        $result = [];

        foreach (array_map('trim', explode(';', $fragment)) as $entry) {
            if (!$entry) {
                continue;
            }

            $parts = (array)preg_split('/\s+/', trim($entry), 2);
            $name = strtolower((string)($parts[0] ?? ''));

            if (!$name) {
                continue;
            }

            $origins = isset($parts[1]) ? array_filter((array)preg_split('/\s+/', trim((string)$parts[1]))) : [];
            $result[$name] = $origins;
        }

        return $result;
    }

    /**
     * @param array<string, string> $securityHeaders
     *
     * @return array<string, string>
     */
    protected function appendToDirective(array $securityHeaders, string $directive, string $origin): array
    {
        $cspValue = $securityHeaders[static::HEADER_CONTENT_SECURITY_POLICY] ?? null;

        if (!$cspValue) {
            $securityHeaders[static::HEADER_CONTENT_SECURITY_POLICY] = sprintf('%s %s', $directive, $origin);

            return $securityHeaders;
        }

        $directives = array_map('trim', explode(';', $cspValue));
        $found = false;

        foreach ($directives as $index => $entry) {
            $parts = (array)preg_split('/\s+/', trim($entry), 2);
            $name = strtolower((string)($parts[0] ?? ''));

            if ($name !== strtolower($directive)) {
                continue;
            }

            $found = true;
            $existingValues = isset($parts[1]) ? (array)preg_split('/\s+/', trim((string)$parts[1])) : [];

            if (in_array($origin, $existingValues, true)) {
                return $securityHeaders;
            }

            $directives[$index] = trim($entry) . ' ' . $origin;

            break;
        }

        if (!$found) {
            $directives[] = sprintf('%s %s', $directive, $origin);
        }

        $securityHeaders[static::HEADER_CONTENT_SECURITY_POLICY] = implode(
            '; ',
            array_filter(array_map('trim', $directives)),
        );

        return $securityHeaders;
    }
}
