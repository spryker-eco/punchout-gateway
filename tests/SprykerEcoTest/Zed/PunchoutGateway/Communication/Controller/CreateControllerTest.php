<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface;
use SprykerEco\Zed\PunchoutGateway\Communication\Controller\CreateController;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\DataProvider\PunchoutConnectionFormDataProvider;
use SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayCommunicationTester;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group Controller
 * @group CreateControllerTest
 */
class CreateControllerTest extends Unit
{
    protected PunchoutGatewayCommunicationTester $tester;

    public function testCreateOciConnectionPrependsOciUrlPrefixToRequestUrl(): void
    {
        // Arrange
        $requestUrlSlug = 'my-oci-slug';
        $idPunchoutConnection = 42;
        $capturedTransfer = null;

        $facadeMock = $this->createMock(PunchoutGatewayFacadeInterface::class);
        $facadeMock->method('createPunchoutConnection')
            ->willReturnCallback(static function (PunchoutConnectionTransfer $transfer) use (&$capturedTransfer, $idPunchoutConnection): PunchoutConnectionTransfer {
                $capturedTransfer = $transfer;

                return (new PunchoutConnectionTransfer())->setIdPunchoutConnection($idPunchoutConnection);
            });

        $controller = new CreateControllerTestDouble();
        $controller->setFacadeDependency($facadeMock);

        // Act
        $result = $controller->exposeExecuteCreateAction([
            'protocolType' => SharedPunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
            'requestUrl' => $requestUrlSlug,
            'name' => 'Test OCI Connection',
        ]);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame(
            SharedPunchoutGatewayConfig::OCI_URL_PREFIX . $requestUrlSlug,
            $capturedTransfer->getRequestUrl(),
        );
        $this->assertStringContainsString(
            sprintf('%s=%d', PunchoutGatewayConfig::PARAM_ID_CONNECTION, $idPunchoutConnection),
            $result->getTargetUrl(),
        );
    }

    public function testCreateCxmlConnectionClearsRequestUrl(): void
    {
        // Arrange
        $idPunchoutConnection = 77;
        $capturedTransfer = null;

        $facadeMock = $this->createMock(PunchoutGatewayFacadeInterface::class);
        $facadeMock->method('createPunchoutConnection')
            ->willReturnCallback(static function (PunchoutConnectionTransfer $transfer) use (&$capturedTransfer, $idPunchoutConnection): PunchoutConnectionTransfer {
                $capturedTransfer = $transfer;

                return (new PunchoutConnectionTransfer())->setIdPunchoutConnection($idPunchoutConnection);
            });

        $controller = new CreateControllerTestDouble();
        $controller->setFacadeDependency($facadeMock);

        // Act
        $result = $controller->exposeExecuteCreateAction([
            'protocolType' => SharedPunchoutGatewayConfig::PROTOCOL_TYPE_CXML,
            'requestUrl' => 'should-be-cleared',
            'name' => 'Test CXML Connection',
        ]);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertNull($capturedTransfer->getRequestUrl());
        $this->assertStringContainsString(
            sprintf('%s=%d', PunchoutGatewayConfig::PARAM_ID_CONNECTION, $idPunchoutConnection),
            $result->getTargetUrl(),
        );
    }

    public function testIndexActionWithInvalidOciFormReturnsViewResponse(): void
    {
        // Arrange
        $formMock = $this->createMock(FormInterface::class);
        $formMock->method('isSubmitted')->willReturn(true);
        $formMock->method('isValid')->willReturn(false);
        $formMock->method('createView')->willReturn(new FormView());

        $controller = $this->createControllerWithFormMock($formMock);

        // Act
        $result = $controller->indexAction(new Request());

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('punchoutConnectionForm', $result);
    }

    public function testIndexActionWithCxmlFormNotSubmittedReturnsViewResponse(): void
    {
        // Arrange
        $formMock = $this->createMock(FormInterface::class);
        $formMock->method('isSubmitted')->willReturn(false);
        $formMock->method('createView')->willReturn(new FormView());

        $controller = $this->createControllerWithFormMock($formMock);

        // Act
        $result = $controller->indexAction(new Request());

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('punchoutConnectionForm', $result);
    }

    protected function createControllerWithFormMock(FormInterface $formMock): mixed
    {
        $dataProviderMock = $this->createMock(PunchoutConnectionFormDataProvider::class);
        $dataProviderMock->method('getData')->willReturn([]);
        $dataProviderMock->method('getOptions')->willReturn([]);

        $factoryMock = $this->createMock(PunchoutGatewayCommunicationFactory::class);
        $factoryMock->method('createPunchoutConnectionFormDataProvider')->willReturn($dataProviderMock);
        $factoryMock->method('createPunchoutConnectionForm')->willReturn($formMock);

        $controller = new CreateControllerTestDouble();
        $controller->setFactoryDependency($factoryMock);

        return $controller;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class CreateControllerTestDouble extends CreateController
{
    public function setFacadeDependency(PunchoutGatewayFacadeInterface $facade): void
    {
        $this->facade = $facade;
    }

    public function setFactoryDependency(PunchoutGatewayCommunicationFactory $factory): void
    {
        $this->factory = $factory;
    }

    public function exposeExecuteCreateAction(array $formData): RedirectResponse
    {
        return $this->executeCreateAction($formData);
    }

    protected function addSuccessMessage($message, array $data = []): static
    {
        return $this;
    }

    protected function assertRedirectIsAllowed(string $redirectUrl): void
    {
    }
}
