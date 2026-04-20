<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Shared\PunchoutGateway\SecurityHeader;

trait SecurityHeaderHelperTrait
{
    protected const string DIRECTIVE_FRAME_ANCESTORS = 'frame-ancestors';

    /**
     * @param array<string> $directives
     *
     * @return array<string>
     */
    protected function addFrameAncestors(array $directives, string $origin): array
    {
        $directives[] = sprintf('%s %s', static::DIRECTIVE_FRAME_ANCESTORS, $origin);

        return $directives;
    }
}
