<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Service\PunchoutGateway;

use Codeception\Test\Unit;
use CXml\Model\Message\PunchOutOrderMessage;
use CXml\Serializer;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\TaxTotalTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoder;
use SprykerEco\Service\PunchoutGateway\Mapper\CxmlPunchoutOrderMessageMapper;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayService;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceFactory;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

/**
 * @group SprykerEcoTest
 * @group Service
 * @group PunchoutGateway
 * @group PunchoutGatewayService
 * @group BuildCxmlPunchoutOrderMessageTest
 */
class BuildCxmlPunchoutOrderMessageTest extends Unit
{
    protected PunchoutGatewayServiceTester $tester;

    protected const string BUYER_COOKIE = 'test-buyer-cookie-abc123';

    protected const string FROM_IDENTITY = 'buyer@example.com';

    protected const string TO_IDENTITY = 'supplier@example.com';

    protected const string SHARED_SECRET = 's3cr3t';

    protected const string CURRENCY = 'EUR';

    protected const string SKU = 'SKU-001';

    public function testBuildCxmlPunchoutOrderMessageTransfersBuyerCookie(): void
    {
        $message = $this->buildAndDecode($this->buildQuote());

        $this->assertSame(static::BUYER_COOKIE, $message->buyerCookie);
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersExtrinsicFields(): void
    {
        $extrinsics = ['UserEmail' => 'john@example.com', 'Department' => 'Engineering'];
        $message = $this->buildAndDecode($this->buildQuote(extrinsics: $extrinsics));
        $decoded = $message->punchOutOrderMessageHeader->getExtrinsicsAsKeyValue();

        $this->assertSame('john@example.com', $decoded['UserEmail']);
        $this->assertSame('Engineering', $decoded['Department']);
    }

    public function testBuildCxmlPunchoutOrderMessageExtrinsicsAreInsideHeader(): void
    {
        $extrinsics = ['UserEmail' => 'john@example.com', 'Department' => 'Engineering'];
        $message = $this->buildAndDecode($this->buildQuote(extrinsics: $extrinsics));

        $headerExtrinsics = $message->punchOutOrderMessageHeader->getExtrinsicsAsKeyValue();

        $this->assertSame('john@example.com', $headerExtrinsics['UserEmail']);
        $this->assertSame('Engineering', $headerExtrinsics['Department']);
        $this->assertCount(count($extrinsics), $headerExtrinsics);
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersQuoteItemSku(): void
    {
        $message = $this->buildAndDecode($this->buildQuote());
        $items = $message->getPunchoutOrderMessageItems();

        $this->assertCount(1, $items);
        $this->assertSame(static::SKU, $items[0]->itemId->supplierPartId);
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersQuoteItemQuantity(): void
    {
        $message = $this->buildAndDecode($this->buildQuote(itemQuantity: 3));

        $this->assertSame(3, $message->getPunchoutOrderMessageItems()[0]->quantity);
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersMultipleItems(): void
    {
        $quote = $this->buildQuote();
        $quote->addItem(
            (new ItemTransfer())
                ->setSku('SKU-002')
                ->setQuantity(2)
                ->setName('Second Product')
                ->setUnitPrice(500)
                ->setGroupKey('SKU-002'),
        );
        $message = $this->buildAndDecode($quote);
        $skus = array_map(static fn ($item) => $item->itemId->supplierPartId, $message->getPunchoutOrderMessageItems());

        $this->assertContains('SKU-001', $skus);
        $this->assertContains('SKU-002', $skus);
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersTotalFromItems(): void
    {
        // 2 items at 1000 cents each = 2000 cents
        $message = $this->buildAndDecode($this->buildQuote(itemUnitPrice: 1000, itemQuantity: 2));
        $total = $message->punchOutOrderMessageHeader->total;

        $this->assertSame(static::CURRENCY, $total->money->currency);
        $this->assertSame(2000, $total->money->getValueCent());
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersShippingTotal(): void
    {
        $totals = (new TotalsTransfer())->setExpenseTotal(750);
        $quote = $this->buildQuote(totals: $totals);
        $quote->addExpense((new ExpenseTransfer())->setType(PunchoutGatewayConfig::SHIPMENT_EXPENSE_TYPE)->setSumGrossPrice(600));
        $quote->addExpense((new ExpenseTransfer())->setType('some other type')->setSumGrossPrice(100));

        $header = $this->buildAndDecode($quote)->punchOutOrderMessageHeader;

        $this->assertNotNull($header->shipping);
        $this->assertSame(static::CURRENCY, $header->shipping->money->currency);
        $this->assertSame(600, $header->shipping->money->getValueCent());
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersNoShippingTotal(): void
    {
        $totals = (new TotalsTransfer())->setExpenseTotal(750);
        $quote = $this->buildQuote(totals: $totals);
        $quote->addExpense((new ExpenseTransfer())->setType('some other type')->setSumGrossPrice(600));

        $header = $this->buildAndDecode($quote)->punchOutOrderMessageHeader;

        $this->assertNull($header->shipping);
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersTaxTotal(): void
    {
        $totals = (new TotalsTransfer())
            ->setExpenseTotal(0)
            ->setTaxTotal((new TaxTotalTransfer())->setAmount(190));
        $header = $this->buildAndDecode($this->buildQuote(totals: $totals))->punchOutOrderMessageHeader;

        $this->assertNotNull($header->tax);
        $this->assertSame(static::CURRENCY, $header->tax->money->currency);
        $this->assertSame(190, $header->tax->money->getValueCent());
    }

    public function testBuildCxmlPunchoutOrderMessageAppliesDiscountToTotal(): void
    {
        // 2 items at 1000 cents each = 2000 cents, minus 200 cents discount = 1800 cents
        $totals = (new TotalsTransfer())->setDiscountTotal(200);
        $total = $this->buildAndDecode($this->buildQuote(itemUnitPrice: 1000, itemQuantity: 2, totals: $totals))
            ->punchOutOrderMessageHeader->total;

        $this->assertSame(static::CURRENCY, $total->money->currency);
        $this->assertSame(1800, $total->money->getValueCent());
    }

    public function testBuildCxmlPunchoutOrderMessageTransfersShipmentAddress(): void
    {
        $address = (new AddressTransfer())
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setAddress1('123 Main Street')
            ->setCity('Springfield')
            ->setIso2Code('US')
            ->setZipCode('12345');
        $header = $this->buildAndDecode($this->buildQuote(shippingAddress: $address))->punchOutOrderMessageHeader;

        $postalAddress = $header->getShipTo()?->address->postalAddress;

        $this->assertNotNull($postalAddress);
        $this->assertSame('Springfield', $postalAddress->city);
        $this->assertContains('123 Main Street', $postalAddress->street);
        $this->assertSame('12345', $postalAddress->postalCode);
    }

    public function testBuildCxmlPunchoutOrderMessageShipmentAddressUsesFullName(): void
    {
        $address = (new AddressTransfer())
            ->setFirstName('Jane')
            ->setLastName('Smith')
            ->setAddress1('1 Commerce Ave')
            ->setCity('Shelbyville')
            ->setIso2Code('US');
        $header = $this->buildAndDecode($this->buildQuote(shippingAddress: $address))->punchOutOrderMessageHeader;

        $this->assertSame('Jane Smith', $header->getShipTo()?->address->name->value);
    }

    private function buildAndDecode(QuoteTransfer $quote): PunchOutOrderMessage
    {
        $service = $this->createService();
        $xml = $service->buildCxmlPunchoutOrderMessage($quote);
        $cxml = $service->decodeCxml($xml);

        $payload = $cxml->message?->payload;
        $this->assertInstanceOf(PunchOutOrderMessage::class, $payload);

        return $payload;
    }

    private function createService(): PunchoutGatewayService
    {
        $mapper = new CxmlPunchoutOrderMessageMapper(
            new CxmlEncoder(Serializer::create()),
            $this->createMock(PunchoutLoggerInterface::class),
        );

        $factory = $this->getMockBuilder(PunchoutGatewayServiceFactory::class)
            ->onlyMethods(['createCxmlPunchoutOrderMessageMapper'])
            ->getMock();
        $factory->method('createCxmlPunchoutOrderMessageMapper')->willReturn($mapper);

        $service = $this->getMockBuilder(PunchoutGatewayService::class)
            ->onlyMethods(['getFactory'])
            ->getMock();
        $service->method('getFactory')->willReturn($factory);

        return $service;
    }

    /**
     * @param array<string, string> $extrinsics
     */
    private function buildQuote(
        array $extrinsics = [],
        int $itemUnitPrice = 1000,
        int $itemQuantity = 1,
        ?TotalsTransfer $totals = null,
        ?AddressTransfer $shippingAddress = null,
    ): QuoteTransfer {
        $cxmlSetupRequest = (new PunchoutCxmlSetupRequestTransfer())
            ->setFromIdentity(static::FROM_IDENTITY)
            ->setToIdentity(static::TO_IDENTITY)
            ->setSenderSharedSecret(static::SHARED_SECRET)
            ->setExtrinsicFields($extrinsics);

        $session = (new PunchoutSessionTransfer())
            ->setBuyerCookie(static::BUYER_COOKIE)
            ->setOperation('create')
            ->setPunchoutData((new PunchoutSessionDataTransfer())->setCxmlSetupRequest($cxmlSetupRequest));

        $quote = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode(static::CURRENCY))
            ->setPunchoutSession($session)
            ->addItem(
                (new ItemTransfer())
                    ->setSku(static::SKU)
                    ->setQuantity($itemQuantity)
                    ->setName('Test Product')
                    ->setUnitPrice($itemUnitPrice)
                    ->setGroupKey(static::SKU),
            );

        if ($totals !== null) {
            $quote->setTotals($totals);
        }

        if ($shippingAddress !== null) {
            $quote->setShippingAddress($shippingAddress);
        }

        return $quote;
    }
}
