<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Generated\Shared\Transfer\CustomerCriteriaFilterTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class CustomerChoiceLoader implements ChoiceLoaderInterface
{
    public function __construct(
        protected readonly CustomerFacadeInterface $customerFacade,
        protected readonly ?int $preselectedIdCustomer = null,
    ) {
    }

    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        if ($this->preselectedIdCustomer === null) {
            return new ArrayChoiceList([], $value);
        }

        $collection = $this->customerFacade->getCustomerCollectionByCriteria(
            (new CustomerCriteriaFilterTransfer())->setCustomerIds([$this->preselectedIdCustomer]),
        );

        $choices = [];

        foreach ($collection->getCustomers() as $customerTransfer) {
            $label = sprintf('%s (%s %s)', $customerTransfer->getEmail(), $customerTransfer->getFirstName(), $customerTransfer->getLastName());
            $choices[$label] = $customerTransfer->getIdCustomerOrFail();
        }

        return new ArrayChoiceList($choices, $value);
    }

    /**
     * @param array<string> $values
     *
     * @return array<int>
     */
    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        $customerIds = array_values(array_filter(
            array_map(fn (string $val) => $val !== '' ? (int)$val : null, $values),
            fn (?int $id) => $id !== null && $id > 0,
        ));

        if ($customerIds === []) {
            return [];
        }

        $collection = $this->customerFacade->getCustomerCollectionByCriteria(
            (new CustomerCriteriaFilterTransfer())->setCustomerIds($customerIds),
        );

        return array_map(
            fn (CustomerTransfer $customer) => $customer->getIdCustomerOrFail(),
            $collection->getCustomers()->getArrayCopy(),
        );
    }

    /**
     * @param array<int> $choices
     *
     * @return array<string>
     */
    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        return array_map(fn (?int $id) => $id !== null ? (string)$id : '', $choices);
    }
}
