<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as PunchoutGatewayPunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 */
class ViewController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function indexAction(Request $request): array|RedirectResponse
    {
        $idPunchoutConnection = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION));

        $punchoutConnectionTransfer = $this->getFacade()->findPunchoutConnectionById($idPunchoutConnection);

        if ($punchoutConnectionTransfer === null) {
            $this->addErrorMessage('Punchout connection with ID %id not found.', ['%id' => $idPunchoutConnection]);

            return $this->redirectResponse(PunchoutGatewayConfig::URL_LIST);
        }

        $data = [
            'punchoutConnection' => $punchoutConnectionTransfer,
        ];

        if ($punchoutConnectionTransfer->getProtocolType() === SharedPunchoutGatewayConfig::PROTOCOL_TYPE_CXML) {
            $punchoutConnectionTransfer->setRequestUrl(
                $this->getFactory()->getConfig()->getBaseUrlYves() . PunchoutGatewayPunchoutGatewayConfig::CXML_SETUP_PREFIX,
            );
        }

        if ($punchoutConnectionTransfer->getProtocolType() === SharedPunchoutGatewayConfig::PROTOCOL_TYPE_OCI) {
            $punchoutConnectionTransfer->setRequestUrl(
                $this->getFactory()->getConfig()->getBaseUrlYves() . $punchoutConnectionTransfer->getRequestUrl(),
            );

            $credentialTable = $this->getFactory()->createPunchoutCredentialTableForView($idPunchoutConnection);

            $data['credentialTable'] = $credentialTable->render();
            $data['defaultUsernameField'] = SharedPunchoutGatewayConfig::OCI_DEFAULT_USERNAME_FIELD;
            $data['defaultPasswordField'] = SharedPunchoutGatewayConfig::OCI_DEFAULT_PASSWORD_FIELD;
            $data['defaultFormMethod'] = SharedPunchoutGatewayConfig::OCI_DEFAULT_FORM_METHOD;
        }

        return $this->viewResponse($data);
    }
}
