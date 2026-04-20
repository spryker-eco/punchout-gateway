<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Plugin\Quote;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\Quote\PunchoutSessionQuoteExpanderPlugin;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayCommunicationTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group Plugin
 * @group Quote
 * @group PunchoutSessionQuoteExpanderPluginTest
 */
class PunchoutSessionQuoteExpanderPluginTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayCommunicationTester $tester;

    public function testExpandReturnsUnchangedQuoteWhenIdQuoteIsNull(): void
    {
        // Arrange
        $quoteTransfer = new QuoteTransfer();
        $plugin = new PunchoutSessionQuoteExpanderPlugin();

        // Act
        $result = $plugin->expand($quoteTransfer);

        // Assert
        $this->assertNull($result->getPunchoutSession());
    }

    public function testExpandSetsPunchoutSessionOnQuoteWhenSessionIsLinked(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
        $connectionTransfer = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStore()]);
        $quoteTransfer = $this->tester->havePersistentQuote(['fk_store' => $storeTransfer->getIdStore(), 'customer' => $customer]);

        $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuote(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
        ]);

        $plugin = new PunchoutSessionQuoteExpanderPlugin();

        // Act
        $result = $plugin->expand($quoteTransfer);

        // Assert
        $this->assertNotNull($result->getPunchoutSession());
        $this->assertSame($quoteTransfer->getIdQuote(), $result->getPunchoutSession()->getIdQuote());
    }
}
