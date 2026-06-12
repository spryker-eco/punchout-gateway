<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Spryker\Zed\Kernel\Exception\Controller\InvalidIdException;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 */
class DeleteController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function indexAction(Request $request): array|RedirectResponse
    {
        $redirectUrl = (string)$request->query->get(PunchoutGatewayConfig::PARAM_REDIRECT_URL, PunchoutGatewayConfig::URL_LIST);

        try {
            $idPunchoutConnection = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION));

            $result = $this->getFacade()->deletePunchoutConnection($idPunchoutConnection);

            $this->addSuccessMessage(
                $result
                ? 'Punchout connection deleted.'
                : 'Punchout connection was not deleted.',
            );
        } catch (InvalidIdException) {
        }

        return $this->redirectResponse($redirectUrl);
    }
}
