<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Customer;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Throwable;

class CxmlCustomerResolver implements CxmlCustomerResolverInterface
{
    protected const string EXTRINSIC_KEY_USER_EMAIL = 'UserEmail';

    public function __construct(
        protected CustomerFacadeInterface $customerFacade,
    ) {
    }

    public function resolveCustomerByEmail(PunchoutSetupRequestTransfer $setupRequestTransfer): ?CustomerTransfer
    {
        $cxmlRequest = $setupRequestTransfer->getCxmlSetupRequest();

        if ($cxmlRequest === null) {
            return null;
        }

        $email = $cxmlRequest->getExtrinsics()[static::EXTRINSIC_KEY_USER_EMAIL] ?? null;

        if ($email === null || $email === '') {
            return null;
        }

        try {
            $customerTransfer = new CustomerTransfer();
            $customerTransfer->setEmail($email);

            return $this->customerFacade->getCustomer($customerTransfer);
        } catch (Throwable) {
            return null;
        }
    }
}
