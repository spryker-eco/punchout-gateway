<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Business;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayBusinessTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Business
 * @group StartPunchoutCxmlSessionTest
 */
class StartPunchoutCxmlSessionTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayBusinessTester $tester;

    protected StoreTransfer $storeTransfer;

    protected function _before(): void
    {
        $this->storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
    }

    public function testStartCxmlSessionWithInvalidTokenReturnsError(): void
    {
        $responseTransfer = $this->tester->getFacade()->startPunchoutCxmlSession(
            $this->buildSessionStartRequest(sprintf('nonexistent_token_%s', uniqid())),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Punchout session is invalid or expired', $responseTransfer->getErrorMessage());
    }

    public function testStartCxmlSessionWithExpiredSessionReturnsError(): void
    {
        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createCxmlConnection();
        $quoteTransfer = $this->createQuoteForCustomer($customerTransfer);

        $sessionTransfer = $this->tester->havePunchoutSession([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'fk_quote' => $quoteTransfer->getIdQuote(),
            'valid_to' => new DateTime('-1 hour'),
        ]);

        $responseTransfer = $this->tester->getFacade()->startPunchoutCxmlSession(
            $this->buildSessionStartRequest($sessionTransfer->getSessionToken()),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Punchout session is invalid or expired', $responseTransfer->getErrorMessage());
    }

    public function testStartCxmlSessionWithInactiveConnectionReturnsError(): void
    {
        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createCxmlConnection(['is_active' => false]);
        $quoteTransfer = $this->createQuoteForCustomer($customerTransfer);

        $sessionTransfer = $this->tester->havePunchoutSession([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'fk_quote' => $quoteTransfer->getIdQuote(),
        ]);

        $responseTransfer = $this->tester->getFacade()->startPunchoutCxmlSession(
            $this->buildSessionStartRequest($sessionTransfer->getSessionToken()),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Punchout session is invalid or expired', $responseTransfer->getErrorMessage());
    }

    public function testStartCxmlSessionWithQuoteReturnsSuccessfulResponse(): void
    {
        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createCxmlConnection();
        $quoteTransfer = $this->createQuoteForCustomer($customerTransfer);

        $sessionTransfer = $this->tester->havePunchoutSession([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'fk_quote' => $quoteTransfer->getIdQuote(),
        ]);

        $responseTransfer = $this->tester->getFacade()->startPunchoutCxmlSession(
            $this->buildSessionStartRequest($sessionTransfer->getSessionToken()),
        );

        $this->assertTrue($responseTransfer->getIsSuccess(), $responseTransfer->getErrorMessage() ?? '');
        $this->assertNotNull($responseTransfer->getCustomer());
        $this->assertSame($customerTransfer->getIdCustomer(), $responseTransfer->getCustomer()->getIdCustomer());
        $this->assertSame($this->storeTransfer->getName(), $responseTransfer->getStoreName());
        $this->assertNotNull($responseTransfer->getQuote());
        $this->assertSame($quoteTransfer->getIdQuote(), $responseTransfer->getQuote()->getIdQuote());
        // Quote's customer is overridden with the freshly resolved customer
        $this->assertSame($customerTransfer->getIdCustomer(), $responseTransfer->getQuote()->getCustomer()->getIdCustomer());
    }

    protected function buildSessionStartRequest(string $sessionToken): PunchoutSessionStartRequestTransfer
    {
        return (new PunchoutSessionStartRequestTransfer())
            ->setSessionToken($sessionToken);
    }

    protected function createCxmlConnection(array $overrides = []): PunchoutConnectionTransfer
    {
        return $this->tester->havePunchoutConnection(array_merge([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'protocol_type' => 'cxml',
            'processor_plugin_class' => DefaultCxmlProcessorPlugin::class,
        ], $overrides));
    }

    protected function createQuoteForCustomer(CustomerTransfer $customerTransfer): QuoteTransfer
    {
        $storeTransfer = $this->tester->getLocator()->store()->facade()->getStoreByName($customerTransfer->getStoreNameOrFail());
        $currencyTransfer = $this->tester->getLocator()->currency()->facade()->fromIsoCode('EUR');

        $quoteTransfer = (new QuoteTransfer())
            ->setName((string)rand())
            ->setCustomer($customerTransfer)
            ->setStore($storeTransfer)
            ->setCurrency($currencyTransfer);

        return $this->tester->getLocator()->quote()->facade()->createQuote($quoteTransfer)->getQuoteTransfer();
    }
}
