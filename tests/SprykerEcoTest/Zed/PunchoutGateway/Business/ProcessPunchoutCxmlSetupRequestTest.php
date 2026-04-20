<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEcoTest\Zed\PunchoutGateway\Helper\CxmlRequestBuilder;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayBusinessTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Business
 * @group ProcessPunchoutCxmlSetupRequestTest
 */
class ProcessPunchoutCxmlSetupRequestTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayBusinessTester $tester;

    protected StoreTransfer $storeTransfer;

    protected function _before(): void
    {
        $this->storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
    }

    public function testProcessCxmlSetupRequestCreateOperationWithoutItemsReturnsSuccessfulResponse(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());
        $sharedSecret = 'test-secret';
        $requestUrl = 'https://test.local/punchout';
        $email = sprintf('test_%s@example.com', uniqid());

        $this->tester->haveConfirmedCustomer(['email' => $email]);
        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'shared_secret' => $sharedSecret,
            'request_url' => $requestUrl,
        ]);

        $rawXml = CxmlRequestBuilder::buildSetupRequest(
            $this->buildCxmlSetupRequestTransfer($senderIdentity, $sharedSecret, 'create', $email),
        );

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );

        $this->assertTrue($responseTransfer->getIsSuccess(), $responseTransfer->getErrorMessage() ?? '');
        $this->assertStringContainsString('/punchout-gateway/cxml/start?session=', $responseTransfer->getStartPageUrl());

        $sessionToken = $this->extractSessionToken($responseTransfer->getStartPageUrl());
        $sessionTransfer = $this->tester->findPunchoutSessionBySessionToken($sessionToken);

        $this->assertNotNull($sessionTransfer);
        $this->assertSame('create', $sessionTransfer->getOperation());
        $this->assertNotNull($sessionTransfer->getPunchoutData()->getCxmlSetupRequest());
        $this->assertSame('create', $sessionTransfer->getPunchoutData()->getCxmlSetupRequest()->getOperation());
        $this->assertSame('https://test.local/return', $sessionTransfer->getPunchoutData()->getCxmlSetupRequest()->getBrowserFormPostUrl());
        $this->assertSame($email, $sessionTransfer->getPunchoutData()->getCxmlSetupRequest()->getExtrinsicFields()['UserEmail']);

        $quoteResponseTransfer = $this->tester->getLocator()->quote()->facade()->findQuoteById($sessionTransfer->getIdQuote());
        $this->assertTrue($quoteResponseTransfer->getIsSuccessful());
    }

    public function testProcessCxmlSetupRequestCreateOperationWithExistingQuoteReusesQuote(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());
        $sharedSecret = 'test-secret';
        $requestUrl = 'https://test.local/punchout';
        $email = sprintf('test_%s@example.com', uniqid());
        $buyerCookie = sprintf('cookie_%s', uniqid());

        $customerTransfer = $this->tester->haveConfirmedCustomer(['email' => $email, 'storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'shared_secret' => $sharedSecret,
            'request_url' => $requestUrl,
        ]);

        $existingQuoteTransfer = $this->createQuoteForCustomer($customerTransfer);
        $this->tester->havePunchoutSession([
            'fk_quote' => $existingQuoteTransfer->getIdQuote(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'buyer_cookie' => $buyerCookie,
        ]);

        $rawXml = CxmlRequestBuilder::buildSetupRequest(
            $this->buildCxmlSetupRequestTransfer($senderIdentity, $sharedSecret, 'create', $email, $buyerCookie),
        );

        $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );
        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );

        $this->assertTrue($responseTransfer->getIsSuccess());
        $this->assertEquals(1, $this->tester->countPunchoutSessionsByBuyerCookie($buyerCookie));

        $sessionToken = $this->extractSessionToken($responseTransfer->getStartPageUrl());
        $sessionTransfer = $this->tester->findPunchoutSessionBySessionToken($sessionToken);

        $this->assertNotNull($sessionTransfer);
        $this->assertSame($existingQuoteTransfer->getIdQuote(), $sessionTransfer->getIdQuote());
        $this->assertSame($email, $sessionTransfer->getPunchoutData()->getCxmlSetupRequest()->getExtrinsicFields()['UserEmail']);
    }

    public function testProcessCxmlSetupRequestEditOperationWithItemsCreatesQuoteWithItems(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());
        $sharedSecret = 'test-secret';
        $requestUrl = 'https://test.local/punchout';
        $email = sprintf('test_%s@example.com', uniqid());

        $this->tester->haveConfirmedCustomer(['email' => $email]);
        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'shared_secret' => $sharedSecret,
            'request_url' => $requestUrl,
        ]);

        $cxmlTransfer = $this->buildCxmlSetupRequestTransfer($senderIdentity, $sharedSecret, 'edit', $email);
        $cxmlTransfer->addItem($this->buildPunchoutItem('SKU-001', '1', '10', 'EUR', 1));
        $cxmlTransfer->addItem($this->buildPunchoutItem('SKU-002', '2', '20', 'EUR', 2));

        $rawXml = CxmlRequestBuilder::buildSetupRequest($cxmlTransfer);

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );

        $this->assertTrue($responseTransfer->getIsSuccess());

        $sessionToken = $this->extractSessionToken($responseTransfer->getStartPageUrl());
        $sessionTransfer = $this->tester->findPunchoutSessionBySessionToken($sessionToken);

        $this->assertNotNull($sessionTransfer);
        $this->assertSame('edit', $sessionTransfer->getOperation());
        $this->assertSame($email, $sessionTransfer->getPunchoutData()->getCxmlSetupRequest()->getExtrinsicFields()['UserEmail']);

        $quoteResponseTransfer = $this->tester->getLocator()->quote()->facade()->findQuoteById($sessionTransfer->getIdQuote());
        $this->assertCount(2, $quoteResponseTransfer->getQuoteTransfer()->getItems());
    }

    public function testProcessCxmlSetupRequestEditOperationWithExistingQuoteReplacesItems(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());
        $sharedSecret = 'test-secret';
        $requestUrl = 'https://test.local/punchout';
        $email = sprintf('test_%s@example.com', uniqid());
        $buyerCookie = sprintf('cookie_%s', uniqid());

        $customerTransfer = $this->tester->haveConfirmedCustomer(['email' => $email, 'storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'shared_secret' => $sharedSecret,
            'request_url' => $requestUrl,
        ]);

        $existingQuoteTransfer = $this->createQuoteForCustomer($customerTransfer);
        $existingQuoteTransfer->addItem(
            (new ItemTransfer())->setSku('OLD-SKU-001')->setQuantity(1)->setUnitPrice(1000),
        );
        $this->tester->getLocator()->quote()->facade()->updateQuote($existingQuoteTransfer);

        $this->tester->havePunchoutSession([
            'fk_quote' => $existingQuoteTransfer->getIdQuote(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'buyer_cookie' => $buyerCookie,
        ]);

        $cxmlTransfer = $this->buildCxmlSetupRequestTransfer($senderIdentity, $sharedSecret, 'edit', $email, $buyerCookie);
        $cxmlTransfer->addItem($this->buildPunchoutItem('NEW-SKU-001', '1', '15', 'EUR', 1));
        $cxmlTransfer->addItem($this->buildPunchoutItem('NEW-SKU-002', '3', '25', 'EUR', 2));

        $rawXml = CxmlRequestBuilder::buildSetupRequest($cxmlTransfer);

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );

        $this->assertTrue($responseTransfer->getIsSuccess());

        $sessionToken = $this->extractSessionToken($responseTransfer->getStartPageUrl());
        $sessionTransfer = $this->tester->findPunchoutSessionBySessionToken($sessionToken);

        $this->assertNotNull($sessionTransfer);
        $this->assertSame($existingQuoteTransfer->getIdQuote(), $sessionTransfer->getIdQuote());
        $this->assertSame($email, $sessionTransfer->getPunchoutData()->getCxmlSetupRequest()->getExtrinsicFields()['UserEmail']);

        $quoteResponseTransfer = $this->tester->getLocator()->quote()->facade()->findQuoteById($sessionTransfer->getIdQuote());
        $this->assertCount(2, $quoteResponseTransfer->getQuoteTransfer()->getItems());
    }

    public function testProcessCxmlSetupRequestWithInvalidXmlReturnsError(): void
    {
        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer('not-valid-xml', 'https://test.local/punchout'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('PunchOut cXML setup request processing failed', $responseTransfer->getErrorMessage());
    }

    public function testProcessCxmlSetupRequestWithMissingSenderIdentityReturnsError(): void
    {
        $cxmlTransfer = (new PunchoutCxmlSetupRequestTransfer())
            ->setSenderSharedSecret('test-secret')
            ->setOperation('create')
            ->setBuyerCookie(uniqid('cookie_'))
            ->setBrowserFormPostUrl('https://test.local/return')
            ->setExtrinsicFields(['UserEmail' => 'test@example.com']);

        $rawXml = CxmlRequestBuilder::buildSetupRequest($cxmlTransfer);

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, 'https://test.local/punchout'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Server identity is missing or empty in the request.', $responseTransfer->getErrorMessage());
    }

    public function testProcessCxmlSetupRequestWithNoMatchingConnectionReturnsError(): void
    {
        $rawXml = CxmlRequestBuilder::buildSetupRequest(
            $this->buildCxmlSetupRequestTransfer('NonExistentIdentity_xxx', 'test-secret', 'create', 'test@example.com'),
        );

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, 'https://test.local/punchout'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('No active connection was found.', $responseTransfer->getErrorMessage());
    }

    public function testProcessCxmlSetupRequestWithInactiveConnectionReturnsError(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());

        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'is_active' => false,
        ]);

        $rawXml = CxmlRequestBuilder::buildSetupRequest(
            $this->buildCxmlSetupRequestTransfer($senderIdentity, 'test-secret', 'create', 'test@example.com'),
        );

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, 'https://test.local/punchout'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('No active connection was found.', $responseTransfer->getErrorMessage());
    }

    public function testProcessCxmlSetupRequestWithWrongSharedSecretReturnsError(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());
        $requestUrl = 'https://test.local/punchout';

        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'shared_secret' => 'correct-secret',
            'request_url' => $requestUrl,
        ]);

        $rawXml = CxmlRequestBuilder::buildSetupRequest(
            $this->buildCxmlSetupRequestTransfer($senderIdentity, 'wrong-secret', 'create', 'test@example.com'),
        );

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Authentication failed', $responseTransfer->getErrorMessage());
    }

    public function testProcessCxmlSetupRequestWithMissingUserEmailReturnsError(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());
        $sharedSecret = 'test-secret';
        $requestUrl = 'https://test.local/punchout';

        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'shared_secret' => $sharedSecret,
            'request_url' => $requestUrl,
        ]);

        $cxmlTransfer = (new PunchoutCxmlSetupRequestTransfer())
            ->setSenderIdentity($senderIdentity)
            ->setSenderSharedSecret($sharedSecret)
            ->setOperation('create')
            ->setBuyerCookie(uniqid('cookie_'))
            ->setBrowserFormPostUrl('https://test.local/return')
            ->setExtrinsicFields([]);

        $rawXml = CxmlRequestBuilder::buildSetupRequest($cxmlTransfer);

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Customer could not be resolved', $responseTransfer->getErrorMessage());
    }

    public function testProcessCxmlSetupRequestWithNonExistentCustomerEmailReturnsError(): void
    {
        $senderIdentity = sprintf('TestIdentity_%s', uniqid());
        $sharedSecret = 'test-secret';
        $requestUrl = 'https://test.local/punchout';

        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $senderIdentity,
            'shared_secret' => $sharedSecret,
            'request_url' => $requestUrl,
        ]);

        $rawXml = CxmlRequestBuilder::buildSetupRequest(
            $this->buildCxmlSetupRequestTransfer($senderIdentity, $sharedSecret, 'create', 'nonexistent@example.com'),
        );

        $responseTransfer = $this->tester->getFacade()->processPunchoutCxmlSetupRequest(
            $this->buildRequestTransfer($rawXml, $requestUrl . '/entry'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Customer could not be resolved', $responseTransfer->getErrorMessage());
    }

    protected function buildRequestTransfer(string $rawXml, string $requestUrl): PunchoutCxmlSetupRequestTransfer
    {
        return (new PunchoutCxmlSetupRequestTransfer())
            ->setRawXml($rawXml)
            ->setRequestUrl($requestUrl);
    }

    protected function buildCxmlSetupRequestTransfer(
        string $senderIdentity,
        string $sharedSecret,
        string $operation,
        string $userEmail,
        ?string $buyerCookie = null,
    ): PunchoutCxmlSetupRequestTransfer {
        return (new PunchoutCxmlSetupRequestTransfer())
            ->setSenderIdentity($senderIdentity)
            ->setSenderSharedSecret($sharedSecret)
            ->setOperation($operation)
            ->setBuyerCookie($buyerCookie ?? uniqid('cookie_'))
            ->setBrowserFormPostUrl('https://test.local/return')
            ->setExtrinsicFields(['UserEmail' => $userEmail]);
    }

    protected function buildPunchoutItem(
        string $supplierPartId,
        string $quantity,
        string $unitPrice,
        string $currency,
        int $lineNumber,
    ): PunchoutItemTransfer {
        return (new PunchoutItemTransfer())
            ->setSupplierPartId($supplierPartId)
            ->setQuantity($quantity)
            ->setUnitPrice($unitPrice)
            ->setCurrency($currency)
            ->setLineNumber($lineNumber);
    }

    protected function extractSessionToken(string $startPageUrl): string
    {
        return substr($startPageUrl, strrpos($startPageUrl, '=') + 1);
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
