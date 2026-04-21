<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote\CxmlPunchoutQuoteFinder;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Business
 * @group CxmlPunchoutQuoteFinderTest
 */
class CxmlPunchoutQuoteFinderTest extends Unit
{
    public function testResolveQuoteReturnsDefaultWhenBuyerCookieIsNull(): void
    {
        $quoteFacadeMock = $this->createQuoteFacadeMock();
        $quoteFacadeMock->expects($this->never())->method('findQuoteById');

        $finder = $this->createFinder($quoteFacadeMock);
        $setupRequestTransfer = $this->buildSetupRequest(null, 1);

        $result = $finder->resolveQuote($setupRequestTransfer);

        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testResolveQuoteReturnsDefaultWhenBuyerCookieIsEmpty(): void
    {
        $quoteFacadeMock = $this->createQuoteFacadeMock();
        $quoteFacadeMock->expects($this->never())->method('findQuoteById');

        $finder = $this->createFinder($quoteFacadeMock);
        $setupRequestTransfer = $this->buildSetupRequest('', 1);

        $result = $finder->resolveQuote($setupRequestTransfer);

        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testResolveQuoteReturnsDefaultWhenNoSessionFoundByBuyerCookie(): void
    {
        $repositoryMock = $this->createRepositoryMock();
        $repositoryMock->method('findPunchoutSessionByBuyerCookie')->willReturn(null);

        $finder = $this->createFinder($this->createQuoteFacadeMock(), $repositoryMock);
        $setupRequestTransfer = $this->buildSetupRequest('buyer-cookie-123', 1);

        $result = $finder->resolveQuote($setupRequestTransfer);

        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testResolveQuoteReturnsDefaultWhenQuoteNotFoundById(): void
    {
        $sessionTransfer = (new PunchoutSessionTransfer())->setIdQuote(99);

        $repositoryMock = $this->createRepositoryMock();
        $repositoryMock->method('findPunchoutSessionByBuyerCookie')->willReturn($sessionTransfer);

        $quoteFacadeMock = $this->createQuoteFacadeMock();
        $quoteFacadeMock->method('findQuoteById')->willReturn(
            (new QuoteResponseTransfer())->setIsSuccessful(false),
        );

        $finder = $this->createFinder($quoteFacadeMock, $repositoryMock);
        $setupRequestTransfer = $this->buildSetupRequest('buyer-cookie-123', 1);

        $result = $finder->resolveQuote($setupRequestTransfer);

        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testResolveQuoteDeletesOldQuoteAndReturnsDefaultWhenStoreIdMismatch(): void
    {
        $quoteStoreId = 1;
        $requestStoreId = 2;

        $existingQuoteTransfer = (new QuoteTransfer())
            ->setIdQuote(42)
            ->setStore((new StoreTransfer())->setIdStore($quoteStoreId));

        $sessionTransfer = (new PunchoutSessionTransfer())->setIdQuote(42);

        $repositoryMock = $this->createRepositoryMock();
        $repositoryMock->method('findPunchoutSessionByBuyerCookie')->willReturn($sessionTransfer);

        $quoteFacadeMock = $this->createQuoteFacadeMock();
        $quoteFacadeMock->method('findQuoteById')->willReturn(
            (new QuoteResponseTransfer())
                ->setIsSuccessful(true)
                ->setQuoteTransfer($existingQuoteTransfer),
        );
        $quoteFacadeMock->expects($this->once())
            ->method('deleteQuote')
            ->with($existingQuoteTransfer);

        $finder = $this->createFinder($quoteFacadeMock, $repositoryMock);
        $setupRequestTransfer = $this->buildSetupRequest('buyer-cookie-123', $requestStoreId);

        $result = $finder->resolveQuote($setupRequestTransfer);

        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testResolveQuoteReturnsExistingQuoteWhenStoreIdMatches(): void
    {
        $storeId = 1;

        $existingQuoteTransfer = (new QuoteTransfer())
            ->setIdQuote(42)
            ->setStore((new StoreTransfer())->setIdStore($storeId));

        $sessionTransfer = (new PunchoutSessionTransfer())->setIdQuote(42);

        $repositoryMock = $this->createRepositoryMock();
        $repositoryMock->method('findPunchoutSessionByBuyerCookie')->willReturn($sessionTransfer);

        $quoteFacadeMock = $this->createQuoteFacadeMock();
        $quoteFacadeMock->method('findQuoteById')->willReturn(
            (new QuoteResponseTransfer())
                ->setIsSuccessful(true)
                ->setQuoteTransfer($existingQuoteTransfer),
        );
        $quoteFacadeMock->expects($this->never())->method('deleteQuote');

        $finder = $this->createFinder($quoteFacadeMock, $repositoryMock);
        $setupRequestTransfer = $this->buildSetupRequest('buyer-cookie-123', $storeId);

        $result = $finder->resolveQuote($setupRequestTransfer);

        $this->assertSame($existingQuoteTransfer, $result);
    }

    protected function buildSetupRequest(?string $buyerCookie, int $idStore): PunchoutSetupRequestTransfer
    {
        $cxmlSetupRequest = (new PunchoutCxmlSetupRequestTransfer())
            ->setBuyerCookie($buyerCookie)
            ->setIdStore($idStore);

        return (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest($cxmlSetupRequest);
    }

    protected function createFinder(
        QuoteFacadeInterface|MockObject|null $quoteFacadeMock = null,
        PunchoutGatewayRepositoryInterface|MockObject|null $repositoryMock = null,
        PunchoutLoggerInterface|MockObject|null $loggerMock = null,
    ): CxmlPunchoutQuoteFinder {
        return new CxmlPunchoutQuoteFinder(
            $quoteFacadeMock ?? $this->createQuoteFacadeMock(),
            $repositoryMock ?? $this->createRepositoryMock(),
            $loggerMock ?? $this->createLoggerMock(),
        );
    }

    protected function createQuoteFacadeMock(): QuoteFacadeInterface|MockObject
    {
        return $this->createMock(QuoteFacadeInterface::class);
    }

    protected function createRepositoryMock(): PunchoutGatewayRepositoryInterface|MockObject
    {
        return $this->createMock(PunchoutGatewayRepositoryInterface::class);
    }

    protected function createLoggerMock(): PunchoutLoggerInterface|MockObject
    {
        return $this->createMock(PunchoutLoggerInterface::class);
    }
}
