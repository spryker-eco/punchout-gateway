<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as PunchoutGatewayPunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 */
class CreateController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function indexAction(Request $request): array|RedirectResponse
    {
        $dataProvider = $this->getFactory()->createPunchoutConnectionFormDataProvider();
        $form = $this->getFactory()->createPunchoutConnectionForm(
            $dataProvider->getData(),
            $dataProvider->getOptions(),
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->executeCreateAction($form->getData());
        }

        return $this->viewResponse([
            'punchoutConnectionForm' => $form->createView(),
            'requestUrlPrefix' => PunchoutGatewayPunchoutGatewayConfig::OCI_URL_PREFIX,
        ]);
    }

    /**
     * @param array<string, mixed> $formData
     */
    protected function executeCreateAction(array $formData): RedirectResponse
    {
        $punchoutConnectionTransfer = (new PunchoutConnectionTransfer())->fromArray($formData, true);
        $punchoutConnectionTransfer->setRequestUrl(PunchoutGatewayPunchoutGatewayConfig::OCI_URL_PREFIX . $punchoutConnectionTransfer->getRequestUrl());

        $punchoutConnectionTransfer = $this->getFacade()->createPunchoutConnection($punchoutConnectionTransfer);

        $this->addSuccessMessage('Punchout connection created successfully.');

        return $this->redirectResponse(sprintf(
            '%s?%s=%d',
            PunchoutGatewayConfig::URL_EDIT,
            PunchoutGatewayConfig::PARAM_ID_CONNECTION,
            $punchoutConnectionTransfer->getIdPunchoutConnectionOrFail(),
        ));
    }
}
