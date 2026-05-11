<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Service\PunchoutGateway\Mapper;

use Codeception\Test\Unit;
use CXml\Serializer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoder;
use SprykerEco\Service\PunchoutGateway\Mapper\CxmlPunchoutOrderMessageMapper;
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

    private function createMapper(?PunchoutLoggerInterface $logger = null): CxmlPunchoutOrderMessageMapper
    {
        return new CxmlPunchoutOrderMessageMapper(
            new CxmlEncoder(Serializer::create()),
            $logger ?? new PunchoutLogger(),
        );
    }

    /**
     * @param array<string, string> $extrinsics
     */
    private function buildCxmlSetupRequest(array $extrinsics): PunchoutCxmlSetupRequestTransfer
    {
        return (new PunchoutCxmlSetupRequestTransfer())
            ->setFromIdentity(static::FROM_IDENTITY)
            ->setToIdentity(static::TO_IDENTITY)
            ->setSenderSharedSecret(static::SHARED_SECRET)
            ->setExtrinsicFields($extrinsics);
    }

    private function buildSession(PunchoutCxmlSetupRequestTransfer $cxmlSetupRequest): PunchoutSessionTransfer
    {
        return (new PunchoutSessionTransfer())
            ->setBuyerCookie(static::BUYER_COOKIE)
            ->setOperation('create')
            ->setPunchoutData((new PunchoutSessionDataTransfer())->setCxmlSetupRequest($cxmlSetupRequest));
    }

    private function buildQuote(PunchoutCxmlSetupRequestTransfer $cxmlSetupRequest): QuoteTransfer
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
