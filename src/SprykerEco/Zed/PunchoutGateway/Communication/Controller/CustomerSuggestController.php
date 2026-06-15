<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Generated\Shared\Transfer\CustomerCriteriaFilterTransfer;
use Generated\Shared\Transfer\CustomerCriteriaSearchTermsTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 */
class CustomerSuggestController extends AbstractController
{
    protected const string PARAM_TERM = 'term';

    protected const string KEY_RESULTS = 'results';

    protected const string KEY_ID = 'id';

    protected const string KEY_TEXT = 'text';

    protected const int DEFAULT_LIMIT = 10;

    public function indexAction(Request $request): JsonResponse
    {
        $searchTerm = (string)$request->query->get(static::PARAM_TERM, '');

        return $this->jsonResponse(
            [
                static::KEY_RESULTS => $this->getCustomerSuggestions($searchTerm),
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getCustomerSuggestions(string $searchTerm): array
    {
        $customerCriteriaFilterTransfer = new CustomerCriteriaFilterTransfer();
        $customerCriteriaFilterTransfer->setSearchTerms(
            (new CustomerCriteriaSearchTermsTransfer())
                ->setEmail($searchTerm)
                ->setFirstName($searchTerm)
                ->setLastName($searchTerm),
        )
        ->setLimit(static::DEFAULT_LIMIT);

        $customerCollectionTransfer = $this->getFactory()
            ->getCustomerFacade()
            ->getCustomerCollectionByCriteria($customerCriteriaFilterTransfer);

        $results = [];

        foreach ($customerCollectionTransfer->getCustomers() as $customerTransfer) {
            $results[] = [
                static::KEY_ID => $customerTransfer->getIdCustomer(),
                static::KEY_TEXT => $this->formatCustomerLabel($customerTransfer),
            ];
        }

        return $results;
    }

    protected function formatCustomerLabel(CustomerTransfer $customerTransfer): string
    {
        return sprintf('%s (%s %s)', $customerTransfer->getEmail(), $customerTransfer->getFirstName(), $customerTransfer->getLastName());
    }
}
