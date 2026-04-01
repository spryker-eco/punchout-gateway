<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Customer;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;

interface CxmlCustomerResolverInterface
{
    /**
     * Resolves a customer from the punchout setup request using the UserEmail extrinsic.
     * Returns null if customer cannot be resolved.
     */
    public function resolveCustomerByEmail(PunchoutSetupRequestTransfer $setupRequestTransfer): ?CustomerTransfer;
}
