<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Customer;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;

class OciCustomerResolver implements OciCustomerResolverInterface
{
    public function __construct(
        protected CustomerFacadeInterface $customerFacade
    ) {
    }

    public function resolveCustomer(PunchoutSetupRequestTransfer $setupRequestTransfer): ?CustomerTransfer
    {
        return $this->customerFacade->findCustomerById(
            (new CustomerTransfer())->setIdCustomer($setupRequestTransfer->getConnection()->getOciConfiguration()->getIdCustomer()),
        );
    }
}
