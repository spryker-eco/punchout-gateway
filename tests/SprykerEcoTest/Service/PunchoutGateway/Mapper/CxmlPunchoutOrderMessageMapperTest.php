<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Service\PunchoutGateway\Mapper;

use Codeception\Test\Unit;
use CXml\Model\CXml;
use CXml\Model\Message\PunchOutOrderMessage;
use CXml\Serializer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoder;
use SprykerEco\Service\PunchoutGateway\Mapper\CxmlPunchoutOrderMessageMapper;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEcoTest\Service\PunchoutGateway\PunchoutGatewayServiceTester;

/**
 * @group SprykerEcoTest
 * @group Service
 * @group PunchoutGateway
 * @group Mapper
 * @group CxmlPunchoutOrderMessageMapperTest
 */
class CxmlPunchoutOrderMessageMapperTest extends Unit
{
    protected PunchoutGatewayServiceTester $tester;

    protected const string BUYER_COOKIE = 'test-buyer-cookie';

    protected const string FROM_IDENTITY = 'buyer@example.com';

    protected const string TO_IDENTITY = 'supplier@example.com';

    protected const string SHARED_SECRET = 's3cr3t';

    public function testMapQuoteToCxmlReturnsEmptyStringWhenNoPunchoutSession(): void
    {
        $quote = (new QuoteTransfer())
            ->addItem((new ItemTransfer())->setSku('SKU-001')->setQuantity(1)->setName('P')->setUnitPrice(100))
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'));

        $this->assertSame('', $this->createMapper()->mapQuoteToCxml($quote));
    }

    public function testMapQuoteToCxmlReturnsEmptyStringWhenNoItems(): void
    {
        $cxmlSetupRequest = $this->buildCxmlSetupRequest([]);
        $quote = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->setPunchoutSession($this->buildSession($cxmlSetupRequest));

        $this->assertSame('', $this->createMapper()->mapQuoteToCxml($quote));
    }

    public function testMapQuoteToCxmlReturnsEmptyStringWhenNoCxmlSetupRequest(): void
    {
        $quote = (new QuoteTransfer())
            ->addItem((new ItemTransfer())->setSku('SKU-001')->setQuantity(1)->setName('P')->setUnitPrice(100))
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->setPunchoutSession((new PunchoutSessionTransfer())->setBuyerCookie(static::BUYER_COOKIE));

        $this->assertSame('', $this->createMapper($this->createMock(PunchoutLoggerInterface::class))->mapQuoteToCxml($quote));
    }

    public function testMapQuoteToCxmlPassesNonBlacklistedExtrinsicsToItems(): void
    {
        $cxmlSetupRequest = $this->buildCxmlSetupRequest([
            'CustomField' => 'custom-value',
            'Department' => 'engineering',
        ]);

        $xml = $this->createMapper()->mapQuoteToCxml($this->buildQuote($cxmlSetupRequest));
        $extrinsics = $this->decodeFirstItemExtrinsics($xml);

        $this->assertSame('custom-value', $extrinsics['CustomField'] ?? null);
        $this->assertSame('engineering', $extrinsics['Department'] ?? null);
    }

    public function testMapQuoteToCxmlSkipsBlacklistedExtrinsics(): void
    {
        $cxmlSetupRequest = $this->buildCxmlSetupRequest([
            'User' => 'john',
            'UserEmail' => 'john@example.com',
            'CustomField' => 'should-appear',
        ]);

        $xml = $this->createMapper()->mapQuoteToCxml($this->buildQuote($cxmlSetupRequest));
        $extrinsics = $this->decodeFirstItemExtrinsics($xml);

        $this->assertArrayNotHasKey('User', $extrinsics);
        $this->assertArrayNotHasKey('UserEmail', $extrinsics);
        $this->assertSame('should-appear', $extrinsics['CustomField'] ?? null);
    }

    public function testMapQuoteToCxmlPassesExtrinsicsToAllItems(): void
    {
        $cxmlSetupRequest = $this->buildCxmlSetupRequest([
            'CustomField' => 'custom-value',
            'Department' => 'engineering',
        ]);

        $quote = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->setPunchoutSession($this->buildSession($cxmlSetupRequest))
            ->addItem(
                (new ItemTransfer())
                    ->setSku('SKU-001')
                    ->setQuantity(1)
                    ->setName('Product One')
                    ->setUnitPrice(1000)
                    ->setGroupKey('SKU-001'),
            )
            ->addItem(
                (new ItemTransfer())
                    ->setSku('SKU-002')
                    ->setQuantity(2)
                    ->setName('Product Two')
                    ->setUnitPrice(2000)
                    ->setGroupKey('SKU-002'),
            )
            ->addItem(
                (new ItemTransfer())
                    ->setSku('SKU-003')
                    ->setQuantity(3)
                    ->setName('Product Three')
                    ->setUnitPrice(3000)
                    ->setGroupKey('SKU-003'),
            );

        $xml = $this->createMapper()->mapQuoteToCxml($quote);
        $allExtrinsics = $this->decodeAllItemExtrinsics($xml);

        $this->assertCount(3, $allExtrinsics);
        foreach ($allExtrinsics as $extrinsics) {
            $this->assertSame('custom-value', $extrinsics['CustomField'] ?? null);
            $this->assertSame('engineering', $extrinsics['Department'] ?? null);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function decodeFirstItemExtrinsics(string $xml): array
    {
        $cxml = Serializer::create()->deserialize($xml);

        $this->assertInstanceOf(CXml::class, $cxml);
        $payload = $cxml->message?->payload;
        $this->assertInstanceOf(PunchOutOrderMessage::class, $payload);

        return $payload->getPunchoutOrderMessageItems()[0]->itemDetail->getExtrinsicsAsKeyValue();
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function decodeAllItemExtrinsics(string $xml): array
    {
        $cxml = Serializer::create()->deserialize($xml);

        $this->assertInstanceOf(CXml::class, $cxml);
        $payload = $cxml->message?->payload;
        $this->assertInstanceOf(PunchOutOrderMessage::class, $payload);

        $result = [];
        foreach ($payload->getPunchoutOrderMessageItems() as $item) {
            $result[] = $item->itemDetail->getExtrinsicsAsKeyValue();
        }

        return $result;
    }

    protected function createMapper(?PunchoutLoggerInterface $logger = null): CxmlPunchoutOrderMessageMapper
    {
        return new CxmlPunchoutOrderMessageMapper(
            new CxmlEncoder(Serializer::create()),
            $logger ?? new PunchoutLogger(),
            new PunchoutGatewayConfig(),
        );
    }

    /**
     * @param array<string, string> $extrinsics
     */
    protected function buildCxmlSetupRequest(array $extrinsics): PunchoutCxmlSetupRequestTransfer
    {
        return (new PunchoutCxmlSetupRequestTransfer())
            ->setFromIdentity(static::FROM_IDENTITY)
            ->setToIdentity(static::TO_IDENTITY)
            ->setSenderSharedSecret(static::SHARED_SECRET)
            ->setExtrinsicFields($extrinsics);
    }

    protected function buildSession(PunchoutCxmlSetupRequestTransfer $cxmlSetupRequest): PunchoutSessionTransfer
    {
        return (new PunchoutSessionTransfer())
            ->setBuyerCookie(static::BUYER_COOKIE)
            ->setOperation('create')
            ->setPunchoutData((new PunchoutSessionDataTransfer())->setCxmlSetupRequest($cxmlSetupRequest));
    }

    protected function buildQuote(PunchoutCxmlSetupRequestTransfer $cxmlSetupRequest): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->setPunchoutSession($this->buildSession($cxmlSetupRequest))
            ->addItem(
                (new ItemTransfer())
                    ->setSku('SKU-001')
                    ->setQuantity(1)
                    ->setName('Test Product')
                    ->setUnitPrice(1000)
                    ->setGroupKey('SKU-001'),
            );
    }
}
