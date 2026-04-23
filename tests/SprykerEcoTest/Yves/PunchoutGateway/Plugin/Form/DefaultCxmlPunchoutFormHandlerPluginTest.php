<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Yves\PunchoutGateway\Plugin\Form;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;
use SprykerEco\Yves\PunchoutGateway\Plugin\Form\DefaultCxmlPunchoutFormHandlerPlugin;
use SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory;
use SprykerEcoTest\Yves\PunchoutGateway\PunchoutGatewayYvesTester;

/**
 * @group SprykerEcoTest
 * @group Yves
 * @group PunchoutGateway
 * @group Plugin
 * @group Form
 * @group DefaultCxmlPunchoutFormHandlerPluginTest
 */
class DefaultCxmlPunchoutFormHandlerPluginTest extends Unit
{
    protected PunchoutGatewayYvesTester $tester;

    public function testHandleReturnsNullWhenBrowserFormPostUrlMissing(): void
    {
        // Arrange
        $plugin = new DefaultCxmlPunchoutFormHandlerPlugin();
        $quoteTransfer = new QuoteTransfer();

        // Act
        $result = $plugin->handle($quoteTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testHandleReturnsNullWhenCxmlPayloadIsEmpty(): void
    {
        // Arrange
        $quoteTransfer = $this->buildQuoteWithSession('https://buyer.example.com/post');

        $serviceMock = $this->createMock(PunchoutGatewayServiceInterface::class);
        $serviceMock->method('buildCxmlPunchoutOrderMessage')->willReturn('');

        $factoryMock = $this->createMock(PunchoutGatewayFactory::class);
        $factoryMock->method('getPunchoutGatewayService')->willReturn($serviceMock);

        $plugin = $this->getMockBuilder(DefaultCxmlPunchoutFormHandlerPlugin::class)
            ->onlyMethods(['getFactory'])
            ->getMock();
        $plugin->method('getFactory')->willReturn($factoryMock);

        // Act
        $result = $plugin->handle($quoteTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testHandleReturnsFormDataTransferWhenPayloadPresent(): void
    {
        // Arrange
        $actionUrl = 'https://buyer.example.com/post';
        $cxmlPayload = '<cXML><Request/></cXML>';
        $quoteTransfer = $this->buildQuoteWithSession($actionUrl);

        $serviceMock = $this->createMock(PunchoutGatewayServiceInterface::class);
        $serviceMock->method('buildCxmlPunchoutOrderMessage')->willReturn($cxmlPayload);

        $factoryMock = $this->createMock(PunchoutGatewayFactory::class);
        $factoryMock->method('getPunchoutGatewayService')->willReturn($serviceMock);

        $plugin = $this->getMockBuilder(DefaultCxmlPunchoutFormHandlerPlugin::class)
            ->onlyMethods(['getFactory'])
            ->getMock();
        $plugin->method('getFactory')->willReturn($factoryMock);

        // Act
        $result = $plugin->handle($quoteTransfer);

        // Assert
        $this->assertInstanceOf(PunchoutFormDataTransfer::class, $result);
        $this->assertSame($actionUrl, $result->getActionUrl());
        $this->assertSame(['cxml-urlencoded' => $cxmlPayload], $result->getFields());
    }

    public function testisApplicableForcXmlRequest()
    {
        $plugin = new DefaultCxmlPunchoutFormHandlerPlugin();

        $quoteTransfer = (new QuoteTransfer())
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                ->setPunchoutData(
                    (new PunchoutSessionDataTransfer())
                    ->setCxmlSetupRequest(
                        (new PunchoutCxmlSetupRequestTransfer()),
                    ),
                ),
            );

        $this->assertTrue($plugin->isApplicable($quoteTransfer));
    }

    public function testisApplicableForNoncXmlRequest()
    {
        $plugin = new DefaultCxmlPunchoutFormHandlerPlugin();

        $quoteTransfer = (new QuoteTransfer())
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                ->setPunchoutData(
                    (new PunchoutSessionDataTransfer()),
                ),
            );

        $this->assertFalse($plugin->isApplicable($quoteTransfer));
    }

    public function testisApplicableForNonPunchout()
    {
        $plugin = new DefaultCxmlPunchoutFormHandlerPlugin();

        $quoteTransfer = (new QuoteTransfer())
            ->setPunchoutSession(
                (new PunchoutSessionTransfer()),
            );

        $this->assertFalse($plugin->isApplicable($quoteTransfer));
    }

    public function testisApplicableForNoPunchoutSession()
    {
        $plugin = new DefaultCxmlPunchoutFormHandlerPlugin();

        $quoteTransfer = (new QuoteTransfer());
        $this->assertFalse($plugin->isApplicable($quoteTransfer));
    }

    protected function buildQuoteWithSession(string $browserFormPostUrl): QuoteTransfer
    {
        $sessionTransfer = (new PunchoutSessionTransfer())
            ->setBrowserFormPostUrl($browserFormPostUrl);

        return (new QuoteTransfer())
            ->setPunchoutSession($sessionTransfer);
    }
}
