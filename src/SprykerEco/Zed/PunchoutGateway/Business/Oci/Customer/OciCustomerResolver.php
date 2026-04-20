<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Customer;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;

class OciCustomerResolver implements OciCustomerResolverInterface
{
    public function __construct(
        protected CustomerFacadeInterface $customerFacade,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function resolveCustomer(PunchoutSetupRequestTransfer $setupRequestTransfer): ?CustomerTransfer
    {
        if (!$setupRequestTransfer->getConnection()?->getIdCustomer()) {
            $this->punchoutLogger->logGenericErrorMessage('Customer ID is null.');

            return null;
        }

        return $this->customerFacade->findCustomerById(
            (new CustomerTransfer())->setIdCustomer($setupRequestTransfer->getConnection()->getIdCustomer()),
        );
    }
}
