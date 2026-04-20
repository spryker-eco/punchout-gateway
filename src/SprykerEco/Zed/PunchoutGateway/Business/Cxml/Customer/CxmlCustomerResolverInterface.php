<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Customer;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;

interface CxmlCustomerResolverInterface
{
    public function resolveCustomerByEmail(PunchoutSetupRequestTransfer $setupRequestTransfer): ?CustomerTransfer;
}
