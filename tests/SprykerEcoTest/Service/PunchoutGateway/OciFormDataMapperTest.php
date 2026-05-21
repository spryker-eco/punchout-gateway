<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEcoTest\Service\PunchoutGateway\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\Mapper\OciFormDataMapper;
use SprykerEco\Service\PunchoutGateway\Mapper\Resolver\FieldValueResolver;
use SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper\ItemTransferFieldMapperPlugin;
use SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper\QuoteTransferFieldMapperPlugin;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEcoTest\Service\PunchoutGateway\PunchoutGatewayServiceTester;

/**
 * @group SprykerEcoTest
 * @group Service
 * @group PunchoutGateway
 * @group Mapper
 * @group OciFormDataMapperTest
 */
class OciFormDataMapperTest extends Unit
{
    protected PunchoutGatewayServiceTester $tester;

    protected const string ACTION_URL = 'https://sap.example.com/oci-return';

    protected const string ITEM_SKU = 'SKU-001';

    protected const string ITEM_NAME = 'Test Product';

    protected const int ITEM_PRICE = 1500;

    protected const int ITEM_QUANTITY = 2;

    public function testMapOciFormDataReturnsNullWhenNoPunchoutSession(): void
    {
        $quote = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR')->setFractionDigits(2));

        $this->assertNull($this->createMapper()->mapOciFormData($quote));
    }

    public function testMapOciFormDataReturnsNullWhenNoBrowserFormPostUrl(): void
    {
        $quote = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR')->setFractionDigits(2))
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                    ->setPunchoutData(
                        (new PunchoutSessionDataTransfer())
                            ->setOciLoginRequest(new PunchoutOciLoginRequestTransfer()),
                    ),
            );

        $this->assertNull($this->createMapper()->mapOciFormData($quote));
    }

    public function testMapOciFormDataReturnsNullWhenNoOciLoginRequest(): void
    {
        $quote = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR')->setFractionDigits(2))
            ->setPunchoutSession(
                (new PunchoutSessionTransfer())
                    ->setBrowserFormPostUrl(static::ACTION_URL)
                    ->setPunchoutData(new PunchoutSessionDataTransfer()),
            );

        $this->assertNull($this->createMapper()->mapOciFormData($quote));
    }

    public function testAllRequiredFieldsPresentWithDefaultMapping(): void
    {
        $result = $this->createMapper()->mapOciFormData($this->buildQuote());

        $this->assertInstanceOf(PunchoutFormDataTransfer::class, $result);

        $fields = $result->getFields();

        $this->assertArrayHasKey('NEW_ITEM-DESCRIPTION[1]', $fields);
        $this->assertArrayHasKey('NEW_ITEM-VENDORMAT[1]', $fields);
        $this->assertArrayHasKey('NEW_ITEM-QUANTITY[1]', $fields);
        $this->assertArrayHasKey('NEW_ITEM-UNIT[1]', $fields);
        $this->assertArrayHasKey('NEW_ITEM-PRICE[1]', $fields);
        $this->assertArrayHasKey('NEW_ITEM-CURRENCY[1]', $fields);
    }

    public function testDefaultMappingResolvesRequiredFieldsFromTransfer(): void
    {
        $fields = $this->createMapper()->mapOciFormData($this->buildQuote())->getFields();

        $this->assertSame(static::ITEM_NAME, $fields['NEW_ITEM-DESCRIPTION[1]']);
        $this->assertSame(static::ITEM_SKU, $fields['NEW_ITEM-VENDORMAT[1]']);
        $this->assertSame((string)static::ITEM_QUANTITY, $fields['NEW_ITEM-QUANTITY[1]']);
        $this->assertSame('EUR', $fields['NEW_ITEM-CURRENCY[1]']);
    }

    public function testActionUrlSetOnResult(): void
    {
        $this->assertSame(static::ACTION_URL, $this->createMapper()->mapOciFormData($this->buildQuote())->getActionUrl());
    }

    public function testOptionalFieldWithNullDefaultSourceIsNotEmitted(): void
    {
        $fields = $this->createMapper()->mapOciFormData($this->buildQuote())->getFields();

        $this->assertArrayNotHasKey('NEW_ITEM-CUST_FIELD1[1]', $fields);
        $this->assertArrayNotHasKey('NEW_ITEM-MATNR[1]', $fields);
    }

    public function testDbMappingOverridesRequiredFieldExpression(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-DESCRIPTION' => 'item.sku']),
        )->getFields();

        $this->assertSame(static::ITEM_SKU, $fields['NEW_ITEM-DESCRIPTION[1]']);
    }

    public function testDbMappingWithUnresolvableExpressionFallsBackForRequiredField(): void
    {
        // 'unknown' plugin key is not registered → resolver returns null → fallback to item.name
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-DESCRIPTION' => 'unknown.field']),
        )->getFields();

        $this->assertSame(static::ITEM_NAME, $fields['NEW_ITEM-DESCRIPTION[1]']);
    }

    public function testDbMappingAddsOptionalFieldWithResolvedValue(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-CUST_FIELD1' => 'item.sku']),
        )->getFields();

        $this->assertSame(static::ITEM_SKU, $fields['NEW_ITEM-CUST_FIELD1[1]']);
    }

    public function testLongtextMappingUsesSpecialIndexedFormat(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-LONGTEXT' => 'item.name']),
        )->getFields();

        $this->assertArrayHasKey('NEW_ITEM-LONGTEXT_1:132[]', $fields);
        $this->assertSame(static::ITEM_NAME, $fields['NEW_ITEM-LONGTEXT_1:132[]']);
        $this->assertArrayNotHasKey('NEW_ITEM-LONGTEXT[1]', $fields);
    }

    public function testDbMappingAppliedToAllItemsInQuote(): void
    {
        $quote = (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('USD')->setFractionDigits(2))
            ->setPunchoutSession($this->buildSession(['NEW_ITEM-CUST_FIELD1' => 'item.sku']))
            ->addItem($this->buildItem('SKU-A', 'Product A', 1000, 1))
            ->addItem($this->buildItem('SKU-B', 'Product B', 2000, 2));

        $fields = $this->createMapper()->mapOciFormData($quote)->getFields();

        $this->assertSame('SKU-A', $fields['NEW_ITEM-CUST_FIELD1[1]']);
        $this->assertSame('SKU-B', $fields['NEW_ITEM-CUST_FIELD1[2]']);
    }

    public function testDoubleQuotedConstantMappingResolvesToLiteralValue(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-CUST_FIELD1' => '"EA"']),
        )->getFields();

        $this->assertSame('EA', $fields['NEW_ITEM-CUST_FIELD1[1]']);
    }

    public function testSingleQuotedConstantMappingResolvesToLiteralValue(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-CUST_FIELD1' => "'EA'"]),
        )->getFields();

        $this->assertSame('EA', $fields['NEW_ITEM-CUST_FIELD1[1]']);
    }

    public function testConcatenationOfTwoPluginExpressions(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-CUST_FIELD1' => 'item.sku&item.name']),
        )->getFields();

        $this->assertSame(static::ITEM_SKU . static::ITEM_NAME, $fields['NEW_ITEM-CUST_FIELD1[1]']);
    }

    public function testConcatenationOfPluginExpressionAndLiteral(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-CUST_FIELD1' => 'item.sku&"_suffix"']),
        )->getFields();

        $this->assertSame(static::ITEM_SKU . '_suffix', $fields['NEW_ITEM-CUST_FIELD1[1]']);
    }

    public function testConcatenationWithNullSegmentTreatedAsEmptyString(): void
    {
        // 'unknown.field' uses unregistered plugin key → resolves to null → treated as ""
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-CUST_FIELD1' => 'unknown.field&"_suffix"']),
        )->getFields();

        $this->assertSame('_suffix', $fields['NEW_ITEM-CUST_FIELD1[1]']);
    }

    public function testConcatenationOfThreeSegments(): void
    {
        $fields = $this->createMapper()->mapOciFormData(
            $this->buildQuote(['NEW_ITEM-CUST_FIELD1' => '"prefix_"&item.sku&"_suffix"']),
        )->getFields();

        $this->assertSame('prefix_' . static::ITEM_SKU . '_suffix', $fields['NEW_ITEM-CUST_FIELD1[1]']);
    }

    protected function createMapper(?PunchoutGatewayConfig $config = null): OciFormDataMapper
    {
        return new OciFormDataMapper(
            $config ?? new PunchoutGatewayConfig(),
            new FieldValueResolver(
                [
                    'item' => new ItemTransferFieldMapperPlugin(),
                    'quote' => new QuoteTransferFieldMapperPlugin(),
                ],
                new PunchoutLogger(),
            ),
        );
    }

    /**
     * @param array<string, string> $connectionMappings
     */
    protected function buildQuote(array $connectionMappings = []): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setCurrency((new CurrencyTransfer())->setCode('EUR')->setFractionDigits(2))
            ->setPunchoutSession($this->buildSession($connectionMappings))
            ->addItem($this->buildItem(static::ITEM_SKU, static::ITEM_NAME, static::ITEM_PRICE, static::ITEM_QUANTITY));
    }

    /**
     * @param array<string, string> $connectionMappings
     */
    protected function buildSession(array $connectionMappings = []): PunchoutSessionTransfer
    {
        $connection = new PunchoutConnectionTransfer();

        if ($connectionMappings !== []) {
            $connection->setMappings($connectionMappings);
        }

        return (new PunchoutSessionTransfer())
            ->setBrowserFormPostUrl(static::ACTION_URL)
            ->setConnection($connection)
            ->setPunchoutData(
                (new PunchoutSessionDataTransfer())
                    ->setOciLoginRequest(new PunchoutOciLoginRequestTransfer()),
            );
    }

    protected function buildItem(string $sku, string $name, int $unitPrice, int $quantity): ItemTransfer
    {
        return (new ItemTransfer())
            ->setSku($sku)
            ->setName($name)
            ->setUnitPrice($unitPrice)
            ->setQuantity($quantity);
    }
}
