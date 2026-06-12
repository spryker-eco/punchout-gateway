<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Expander;

use Generated\Shared\Transfer\PunchoutSessionTransfer;

interface OciSecurityHeaderExpanderInterface
{
    /**
     * @param array<string> $directives
     *
     * @return array<string>
     */
    public function expand(array $directives, PunchoutSessionTransfer $punchoutSessionTransfer, string $origin): array;
}
