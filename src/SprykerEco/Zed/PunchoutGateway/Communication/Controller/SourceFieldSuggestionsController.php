<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class SourceFieldSuggestionsController extends AbstractController
{
    protected const string PARAM_TERM = 'term';

    public function indexAction(Request $request): JsonResponse
    {
        $term = (string)$request->query->get(static::PARAM_TERM, '');

        $suggestions = $this->getFactory()
            ->getPunchoutGatewayService()
            ->getSourceFieldSuggestions();

        $suggestions = $this->getFactory()
            ->createSourceFieldSuggestionFilter()
            ->filterByTerm($suggestions, $term);

        return new JsonResponse($suggestions);
    }
}
