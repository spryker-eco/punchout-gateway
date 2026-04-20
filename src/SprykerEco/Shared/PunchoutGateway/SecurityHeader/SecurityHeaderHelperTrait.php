<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
