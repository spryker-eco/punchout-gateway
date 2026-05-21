<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class SourceFieldSuggestionsController extends AbstractController
{
    public function indexAction(): JsonResponse
    {
        $suggestions = $this->getFactory()
            ->getPunchoutGatewayService()
            ->getSourceFieldSuggestions();

        $response = new JsonResponse(['suggestions' => $suggestions]);
        $response->headers->set('Cache-Control', 'private, max-age=60');

        return $response;
    }
}
