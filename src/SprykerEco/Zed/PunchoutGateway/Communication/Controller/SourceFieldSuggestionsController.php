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

    protected const string SEPARATOR = '&';

    public function indexAction(Request $request): JsonResponse
    {
        $term = (string)$request->query->get(static::PARAM_TERM, '');

        $suggestions = $this->getFactory()
            ->getPunchoutGatewayService()
            ->getSourceFieldSuggestions();

        $suggestions = $this->filterSuggestionsByTerm($suggestions, $term);

        return new JsonResponse($suggestions);
    }

    /**
     * @param array<string> $suggestions
     *
     * @return array<string>
     */
    protected function filterSuggestionsByTerm(array $suggestions, string $term): array
    {
        if ($term === '') {
            return $suggestions;
        }

        $separatorPosition = strrpos($term, static::SEPARATOR);

        if ($separatorPosition !== false) {
            $prefix = substr($term, 0, $separatorPosition + 1);

            $term = substr($term, $separatorPosition + 1);
        }

        $suggestions = array_values(
            array_filter(
                $suggestions,
                static fn (string $suggestion): bool => stripos($suggestion, $term) !== false,
            ),
        );

        if ($separatorPosition !== false) {
            $suggestions = substr_replace($suggestions, $prefix, 0, 0);
        }

        return $suggestions;
    }
}
