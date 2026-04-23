<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Yves\PunchoutGateway\Plugin\Form;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Yves\PunchoutGateway\FormBuilder\OciFormFieldBuilder;
use SprykerEco\Yves\PunchoutGateway\Plugin\Form\DefaultOciPunchoutFormHandlerPlugin;
use SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory;
use SprykerEcoTest\Yves\PunchoutGateway\PunchoutGatewayYvesTester;

/**
 * @group SprykerEcoTest
 * @group Yves
 * @group PunchoutGateway
 * @group Plugin
 * @group Form
 * @group DefaultOciPunchoutFormHandlerPluginTest
 */
class DefaultOciPunchoutFormHandlerPluginTest extends Unit
{
    protected const string ACTION_URL = 'https://buyer.example.com/post';

    protected PunchoutGatewayYvesTester $tester;

    public function testHandleReturnsNullWhenPunchoutSessionMissing(): void
    {
        // Arrange
        $plugin = $this->buildPluginWithRealBuilder();
        $quoteTransfer = new QuoteTransfer();

        // Act
        $result = $plugin->handle($quoteTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testHandleReturnsNullWhenOciLoginRequestMissing(): void
    {
        // Arrange
        $plugin = $this->buildPluginWithRealBuilder();
        $quoteTransfer = (new QuoteTransfer())
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                    ->setBrowserFormPostUrl(static::ACTION_URL)
                    ->setPunchoutData(new PunchoutSessionDataTransfer()),
            );

        // Act
        $result = $plugin->handle($quoteTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testHandleReturnsFormDataTransferWhenItemsPresent(): void
    {
        // Arrange
        $plugin = $this->buildPluginWithRealBuilder();
        $quoteTransfer = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR')->setFractionDigits(2))
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                    ->setBrowserFormPostUrl(static::ACTION_URL)
                    ->setPunchoutData(
                        (new PunchoutSessionDataTransfer())
                            ->setOciLoginRequest(new PunchoutOciLoginRequestTransfer()),
                    ),
            )
            ->addItem(
                (new ItemTransfer())
                    ->setName('Widget')
                    ->setSku('SKU-1')
                    ->setQuantity(2)
                    ->setUnitPrice(1500),
            );

        // Act
        $result = $plugin->handle($quoteTransfer);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(static::ACTION_URL, $result->getActionUrl());
        $fields = $result->getFields();
        $this->assertSame('Widget', $fields['NEW_ITEM-DESCRIPTION[1]']);
        $this->assertSame('2', $fields['NEW_ITEM-QUANTITY[1]']);
        $this->assertSame('15.000', $fields['NEW_ITEM-PRICE[1]']);
        $this->assertSame('EUR', $fields['NEW_ITEM-CURRENCY[1]']);
        $this->assertSame('SKU-1', $fields['NEW_ITEM-VENDORMAT[1]']);
    }

    public function testHandleReturnsFormDataTransferWhenNoItemsPresent(): void
    {
        // Arrange
        $plugin = $this->buildPluginWithRealBuilder();
        $quoteTransfer = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR')->setFractionDigits(2))
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                    ->setBrowserFormPostUrl(static::ACTION_URL)
                    ->setPunchoutData(
                        (new PunchoutSessionDataTransfer())
                            ->setOciLoginRequest(new PunchoutOciLoginRequestTransfer()),
                    ),
            );

        // Act
        $result = $plugin->handle($quoteTransfer);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(static::ACTION_URL, $result->getActionUrl());
        $fields = $result->getFields();
        $this->assertEmpty($fields);
    }

    public function testisApplicableForOCIRequest()
    {
        $plugin = new DefaultOciPunchoutFormHandlerPlugin();

        $quoteTransfer = (new QuoteTransfer())
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                    ->setPunchoutData(
                        (new PunchoutSessionDataTransfer())
                            ->setOciLoginRequest(
                                (new PunchoutOciLoginRequestTransfer()),
                            ),
                    ),
            );

        $this->assertTrue($plugin->isApplicable($quoteTransfer));
    }

    public function testisApplicableForNonOCIRequest()
    {
        $plugin = new DefaultOciPunchoutFormHandlerPlugin();

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
        $plugin = new DefaultOciPunchoutFormHandlerPlugin();

        $quoteTransfer = (new QuoteTransfer())
            ->setPunchoutSession(
                (new PunchoutSessionTransfer()),
            );

        $this->assertFalse($plugin->isApplicable($quoteTransfer));
    }

    public function testisApplicableForNoPunchoutSession()
    {
        $plugin = new DefaultOciPunchoutFormHandlerPlugin();

        $quoteTransfer = (new QuoteTransfer());
        $this->assertFalse($plugin->isApplicable($quoteTransfer));
    }

    protected function buildPluginWithRealBuilder(): DefaultOciPunchoutFormHandlerPlugin
    {
        $factoryMock = $this->createMock(PunchoutGatewayFactory::class);
        $factoryMock->method('createOciFormFieldBuilder')->willReturn(new OciFormFieldBuilder());

        $plugin = $this->getMockBuilder(DefaultOciPunchoutFormHandlerPlugin::class)
            ->onlyMethods(['getFactory'])
            ->getMock();
        $plugin->method('getFactory')->willReturn($factoryMock);

        return $plugin;
    }
}
