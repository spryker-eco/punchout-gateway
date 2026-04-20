<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Customer;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use Throwable;

class CxmlCustomerResolver implements CxmlCustomerResolverInterface
{
    protected const string EXTRINSIC_KEY_USER_EMAIL = 'UserEmail';

    public function __construct(
        protected CustomerFacadeInterface $customerFacade,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function resolveCustomerByEmail(PunchoutSetupRequestTransfer $setupRequestTransfer): ?CustomerTransfer
    {
        $cxmlRequest = $setupRequestTransfer->getCxmlSetupRequest();

        if ($cxmlRequest === null) {
            $this->punchoutLogger->logAuthenticationFailure('', 'No setup request data was found.');

            return null;
        }

        $email = $cxmlRequest->getExtrinsicFields()[static::EXTRINSIC_KEY_USER_EMAIL] ?? null;

        if ($email === null || $email === '') {
            $this->punchoutLogger->logAuthenticationFailure('', sprintf('Empty email in extrinsics %s.', static::EXTRINSIC_KEY_USER_EMAIL));

            return null;
        }

        try {
            $customerTransfer = new CustomerTransfer();
            $customerTransfer->setEmail($email);

            return $this->customerFacade->getCustomer($customerTransfer);
        } catch (Throwable $t) {
            $this->punchoutLogger->logAuthenticationFailure($email, sprintf('Unhandled error locating customer by email: %s.', $t->getMessage()));

            return null;
        }
    }
}
