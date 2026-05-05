<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 */
class CredentialController extends AbstractController
{
    public function credentialTableAction(Request $request): JsonResponse
    {
        $idPunchoutConnection = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION));
        $table = $this->getFactory()->createPunchoutCredentialTable($idPunchoutConnection);

        return $this->jsonResponse($table->fetchData());
    }

    public function toggleIsActiveAction(Request $request): RedirectResponse
    {
        $idPunchoutCredential = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CREDENTIAL));
        $redirectUrl = (string)$request->query->get(PunchoutGatewayConfig::PARAM_REDIRECT_URL, PunchoutGatewayConfig::URL_LIST);

        $punchoutCredentialTransfer = $this->getFacade()->findPunchoutCredentialById($idPunchoutCredential);

        if ($punchoutCredentialTransfer === null) {
            $this->addErrorMessage('Punchout connection with ID %id not found.', ['%id' => $idPunchoutCredential]);

            return $this->redirectResponse($redirectUrl);
        }

        $punchoutCredentialTransfer->setIsActive(!$punchoutCredentialTransfer->getIsActive());
        $this->getFacade()->updatePunchoutCredential($punchoutCredentialTransfer);

        if ($punchoutCredentialTransfer->getIsActive()) {
            $this->addSuccessMessage('Credentials `%username` were activated.', ['%username' => $punchoutCredentialTransfer->getUsername()]);
        } else {
            $this->addSuccessMessage('Credentials `%username` were deactivated.', ['%username' => $punchoutCredentialTransfer->getUsername()]);
        }

        return $this->redirectResponse($redirectUrl);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function addCredentialAction(Request $request): array|RedirectResponse
    {
        $idPunchoutConnection = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION));

        $dataProvider = $this->getFactory()->createPunchoutCredentialFormDataProvider();
        $form = $this->getFactory()->createPunchoutCredentialForm(
            $dataProvider->getData(),
            $dataProvider->getOptions(),
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->executeAddCredentialAction($idPunchoutConnection, $form->getData());
        }

        return $this->viewResponse([
            'credentialForm' => $form->createView(),
            'idPunchoutConnection' => $idPunchoutConnection,
        ]);
    }

    public function deleteCredentialAction(Request $request): RedirectResponse
    {
        $idPunchoutCredential = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CREDENTIAL));
        $idPunchoutConnection = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION));

        $this->getFacade()->deletePunchoutCredential($idPunchoutCredential);
        $this->addSuccessMessage('Credential was removed.');

        return $this->redirectResponse(
            sprintf('%s?%s=%d', PunchoutGatewayConfig::URL_EDIT, PunchoutGatewayConfig::PARAM_ID_CONNECTION, $idPunchoutConnection),
        );
    }

    public function tableAction(Request $request): JsonResponse
    {
        $idPunchoutConnection = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION));
        $table = $this->getFactory()->createPunchoutCredentialTable($idPunchoutConnection);

        return $this->jsonResponse($table->fetchData());
    }

    /**
     * @param array<string, mixed> $formData
     */
    protected function executeAddCredentialAction(int $idPunchoutConnection, array $formData): RedirectResponse
    {
        $redirectUrl = sprintf('%s?%s=%d', PunchoutGatewayConfig::URL_CONNECTION, PunchoutGatewayConfig::PARAM_ID_CONNECTION, $idPunchoutConnection);

        $password = $formData['password'] ?? null;

        if (!$password) {
            $this->addErrorMessage('Password cannot be empty.');

            return $this->redirectResponse($redirectUrl);
        }

        $punchoutCredentialTransfer = (new PunchoutCredentialTransfer())->fromArray($formData, true);
        $punchoutCredentialTransfer->setIdPunchoutConnection($idPunchoutConnection);
        $punchoutCredentialTransfer->setPasswordHash(password_hash($password, PASSWORD_DEFAULT));

        $this->getFacade()->createPunchoutCredential($punchoutCredentialTransfer);
        $this->addSuccessMessage('Credential was added.');

        return $this->redirectResponse($redirectUrl);
    }
}
