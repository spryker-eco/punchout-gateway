<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Controller;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Exception\RuntimeException;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Spryker\Zed\Kernel\Exception\Controller\InvalidIdException;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as PunchoutGatewayPunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\DataTransformer\PunchoutCxmlMappingDataTransformer;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\DataTransformer\PunchoutOciMappingDataTransformer;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutConnectionFormType;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 */
class EditController extends AbstractController
{
    use LoggerTrait;

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function indexAction(Request $request): array|RedirectResponse
    {
        try {
            $id = $request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION);
            $idPunchoutConnection = $this->castId($id);
        } catch (InvalidIdException) {
            $this->addErrorMessage('Punchout connection ID `%id` is invalid.', ['%id' => $id]);

            return $this->redirectResponse(PunchoutGatewayConfig::URL_LIST);
        }

        $punchoutConnectionTransfer = $this->getFacade()->findPunchoutConnectionById($idPunchoutConnection);

        if ($punchoutConnectionTransfer === null) {
            $this->addErrorMessage('Punchout connection with ID %id not found.', ['%id' => $idPunchoutConnection]);

            return $this->redirectResponse(PunchoutGatewayConfig::URL_LIST);
        }

        $dataProvider = $this->getFactory()->createPunchoutConnectionFormDataProvider();
        $form = $this->getFactory()->createPunchoutConnectionForm(
            $dataProvider->getData($punchoutConnectionTransfer),
            $dataProvider->getOptions($punchoutConnectionTransfer),
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->executeUpdateAction($idPunchoutConnection, $form->getData());

            if ($result !== null) {
                return $result;
            }
        }

        $punchoutCredentialFormDataProvider = $this->getFactory()->createPunchoutCredentialFormDataProvider();
        $credentialForm = $this->getFactory()->createPunchoutCredentialForm(
            $punchoutCredentialFormDataProvider->getData(),
            $punchoutCredentialFormDataProvider->getOptions(),
        );
        $credentialTable = $this->getFactory()->createPunchoutCredentialTable($idPunchoutConnection);

        return $this->viewResponse([
            'punchoutConnectionForm' => $form->createView(),
            'punchoutConnection' => $punchoutConnectionTransfer,
            'credentialForm' => $credentialForm->createView(),
            'credentialTable' => $credentialTable->render(),
            'idPunchoutConnection' => $idPunchoutConnection,
            'requestUrlPrefix' => PunchoutGatewayPunchoutGatewayConfig::OCI_URL_PREFIX,
            'reservedExtrinsicNames' => $this->getFactory()->getConfig()->getExtrinsicBlackList(),
            'availableSourceExpressionData' => $this->getFactory()->getPunchoutGatewayService()->getSourceFieldSuggestionNames(),
        ]);
    }

    public function toggleIsActiveAction(Request $request): RedirectResponse
    {
        $idPunchoutConnection = $this->castId($request->query->get(PunchoutGatewayConfig::PARAM_ID_CONNECTION));
        $redirectUrl = (string)$request->query->get(PunchoutGatewayConfig::PARAM_REDIRECT_URL, PunchoutGatewayConfig::URL_LIST);

        $punchoutConnectionTransfer = $this->getFacade()->findPunchoutConnectionById($idPunchoutConnection);

        if ($punchoutConnectionTransfer === null) {
            $this->addErrorMessage('Punchout connection with ID %id not found.', ['%id' => $idPunchoutConnection]);

            return $this->redirectResponse($redirectUrl);
        }

        $punchoutConnectionTransfer->setIsActive(!$punchoutConnectionTransfer->getIsActive());
        $this->getFacade()->updatePunchoutConnection($punchoutConnectionTransfer);

        $this->addSuccessMessage(
            $punchoutConnectionTransfer->getIsActive()
                ? 'Connection `%name` activated.'
                : 'Connection `%name` deactivated.',
            ['%name' => $punchoutConnectionTransfer->getName()],
        );

        return $this->redirectResponse($redirectUrl);
    }

    /**
     * @param array<string, mixed> $formData
     */
    protected function executeUpdateAction(int $idPunchoutConnection, array $formData): ?RedirectResponse
    {
        $formData[PunchoutConnectionTransfer::ID_PUNCHOUT_CONNECTION] = $idPunchoutConnection;

        $punchoutConnectionTransfer = (new PunchoutConnectionTransfer())->fromArray($formData, true);

        $mappings = [];

        if ($punchoutConnectionTransfer->getProtocolType() === PunchoutGatewayPunchoutGatewayConfig::PROTOCOL_TYPE_CXML) {
            $punchoutConnectionTransfer->setRequestUrl(null);
            $mappings = $formData[PunchoutConnectionFormType::FIELD_CXML_CONFIGURATION][PunchoutCxmlMappingDataTransformer::MAPPINGS] ?? [];
        }

        if ($punchoutConnectionTransfer->getProtocolType() === PunchoutGatewayPunchoutGatewayConfig::PROTOCOL_TYPE_OCI) {
            $punchoutConnectionTransfer->setRequestUrl(PunchoutGatewayPunchoutGatewayConfig::OCI_URL_PREFIX . $punchoutConnectionTransfer->getRequestUrl());
            $mappings = $formData[PunchoutConnectionFormType::FIELD_OCI_CONFIGURATION][PunchoutOciMappingDataTransformer::MAPPINGS] ?? [];
        }

        $punchoutConnectionTransfer->setMappings($mappings);

        try {
            $this->getFacade()->updatePunchoutConnection($punchoutConnectionTransfer);

            $this->addSuccessMessage('Punchout connection saved successfully.');

            return $this->redirectResponse(
                sprintf('%s?%s=%d', PunchoutGatewayConfig::URL_EDIT, PunchoutGatewayConfig::PARAM_ID_CONNECTION, $idPunchoutConnection),
            );
        } catch (PropelException | RuntimeException $e) {
            $this->addErrorMessage('Punchout connection was not saved, check the logs.');
            $this->getLogger()->error(
                'Punchout connection was not saved, check the logs.',
                [
                    'exception' => $e,
                    'previousException' => $e->getPrevious(),
                ],
            );
        }

        return null;
    }
}
