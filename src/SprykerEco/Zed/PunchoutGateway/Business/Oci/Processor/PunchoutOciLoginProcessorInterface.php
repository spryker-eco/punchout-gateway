<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Processor;

use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;

interface PunchoutOciLoginProcessorInterface
{
    public function processLoginRequest(PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer): PunchoutSessionStartResponseTransfer;
}
