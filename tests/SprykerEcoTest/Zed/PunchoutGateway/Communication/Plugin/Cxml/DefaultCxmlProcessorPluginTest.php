<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Plugin\Cxml;

use Codeception\Test\Unit;
use CXml\Model\Credential;
use CXml\Model\CXml;
use CXml\Model\Header;
use CXml\Model\Party;
use CXml\Model\PayloadIdentity;
use CXml\Model\Request\PunchOutSetupRequest;
use CXml\Model\Request\Request;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutAddressTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlConfigurationTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutItemTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayCommunicationTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group Plugin
 * @group Cxml
 * @group DefaultCxmlProcessorPluginTest
 */
class DefaultCxmlProcessorPluginTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayCommunicationTester $tester;

    protected StoreTransfer $storeTransfer;

    protected function _before(): void
    {
        $this->storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
    }

    public function testParseCxmlRequestPopulatesTransferFromCxmlObject(): void
    {
        // Arrange
        $senderCredential = (new Credential('NetworkId', 'test-sender-identity'))
            ->setSharedSecret('test-shared-secret');
        $header = new Header(
            new Party(new Credential('NetworkId', 'buyer-identity')),
            new Party(new Credential('DUNS', 'supplier-identity')),
            new Party($senderCredential, 'Test Agent'),
        );
        $punchOutSetupRequest = new PunchOutSetupRequest(
            'buyer-cookie-123',
            'https://buyer.example.com/return',
            'https://test.local/supplier-setup',
            null,
            null,
            PunchoutGatewayConfig::OPERATION_CREATE,
        );
        $cxml = CXml::forRequest(
            new PayloadIdentity(sprintf('%s@test.local', uniqid())),
            new Request($punchOutSetupRequest, deploymentMode: CXml::DEPLOYMENT_PROD),
            $header,
        );
        $cxmlSetupRequestTransfer = new PunchoutCxmlSetupRequestTransfer();
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->parseCxmlRequest($cxmlSetupRequestTransfer, $cxml);

        // Assert
        $this->assertSame('test-sender-identity', $result->getSenderIdentity());
        $this->assertSame('buyer-cookie-123', $result->getBuyerCookie());
        $this->assertSame(PunchoutGatewayConfig::OPERATION_CREATE, $result->getOperation());
        $this->assertSame('https://buyer.example.com/return', $result->getBrowserFormPostUrl());
    }

    public function testAuthenticateWithMatchingUrlAndCorrectSecretReturnsConnection(): void
    {
        // Arrange
        $connectionTransfer = $this->buildConnectionWithCxmlConfig('https://test.local/cxml', 'correct-secret');
        $setupRequestTransfer = $this->buildCxmlSetupRequest(
            $connectionTransfer,
            'https://test.local/cxml/setup',
            'correct-secret',
        );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->authenticate($setupRequestTransfer);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($connectionTransfer->getIdPunchoutConnection(), $result->getIdPunchoutConnection());
    }

    public function testAuthenticateWithWrongSharedSecretReturnsNull(): void
    {
        // Arrange
        $connectionTransfer = $this->buildConnectionWithCxmlConfig('https://test.local/cxml', 'correct-secret');
        $setupRequestTransfer = $this->buildCxmlSetupRequest(
            $connectionTransfer,
            'https://test.local/cxml/setup',
            'wrong-secret',
        );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->authenticate($setupRequestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testResolveCustomerWithValidEmailReturnsCustomerTransfer(): void
    {
        // Arrange
        $email = sprintf('test-%s@example.com', uniqid());
        $customerTransfer = $this->tester->haveConfirmedCustomer([
            'email' => $email,
            'storeName' => $this->storeTransfer->getName(),
        ]);
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())->setExtrinsicFields(['UserEmail' => $email]),
            );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->resolveCustomer($setupRequestTransfer);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($customerTransfer->getIdCustomer(), $result->getIdCustomer());
    }

    public function testResolveCustomerWithNoCxmlSetupRequestReturnsNull(): void
    {
        // Arrange
        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->resolveCustomer($setupRequestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testResolveCustomerWithMissingUserEmailExtrinsicReturnsNull(): void
    {
        // Arrange
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())->setExtrinsicFields([]),
            );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->resolveCustomer($setupRequestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testResolveQuoteWithEmptyBuyerCookieReturnsNewDefaultQuote(): void
    {
        // Arrange
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(new PunchoutCxmlSetupRequestTransfer());
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->resolveQuote($setupRequestTransfer);

        // Assert
        $this->assertNull($result->getIdQuote());
        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testResolveQuoteWithUnknownBuyerCookieReturnsNewDefaultQuote(): void
    {
        // Arrange
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())->setBuyerCookie(sprintf('no-such-cookie-%s', uniqid())),
            );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->resolveQuote($setupRequestTransfer);

        // Assert
        $this->assertNull($result->getIdQuote());
        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testResolveQuoteWithKnownBuyerCookieReturnsExistingQuote(): void
    {
        // Arrange
        $customer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createCxmlConnection();
        $persistedQuote = $this->tester->havePersistentQuote([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'customer' => $customer,
        ]);
        $buyerCookie = sprintf('buyer-cookie-%s', base64_encode(random_bytes(10)));

        $this->tester->havePunchoutSession([
            'fk_quote' => $persistedQuote->getIdQuote(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'buyer_cookie' => $buyerCookie,
        ]);

        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())->setBuyerCookie($buyerCookie)
                    ->setIdStore($this->storeTransfer->getIdStore()),
            );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->resolveQuote($setupRequestTransfer);

        // Assert
        $this->assertSame($persistedQuote->getIdQuote(), $result->getIdQuote());
    }

    public function testExpandQuoteWithNoCxmlSetupRequestReturnsUnchangedQuote(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->setName('my-quote');
        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->expandQuote($quoteTransfer, $setupRequestTransfer);

        // Assert
        $this->assertSame('my-quote', $result->getName());
        $this->assertNull($result->getShippingAddress());
    }

    public function testExpandQuoteWithShippingAddressMapsAddressToQuote(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addItem(new ItemTransfer())
            ->addItem(new ItemTransfer())
            ->addItem(new ItemTransfer());

        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())
                    ->setOperation(PunchoutGatewayConfig::OPERATION_CREATE)
                    ->setShipTo(
                        (new PunchoutAddressTransfer())
                            ->setCity('Berlin')
                            ->setCountryCode('DE'),
                    ),
            );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $resultQuoteTransfer = $plugin->expandQuote($quoteTransfer, $setupRequestTransfer);

        // Assert
        $this->assertNotNull($resultQuoteTransfer->getShippingAddress());
        $this->assertSame('Berlin', $resultQuoteTransfer->getShippingAddress()->getCity());
        $this->assertSame('DE', $resultQuoteTransfer->getShippingAddress()->getIso2Code());

        $expectedAddress = $resultQuoteTransfer->getShippingAddress();

        foreach ($resultQuoteTransfer->getItems() as $itemTransfer) {
            $this->assertNotNull($itemTransfer->getShipment());
            $this->assertSame($expectedAddress, $itemTransfer->getShipment()->getShippingAddress());
        }
    }

    /**
     * @skip Managing items will be done in phase 3
     */
    public function testExpandQuoteWithEditOperationMapsItemsToQuote(): void
    {
        // Arrange
        $quoteTransfer = new QuoteTransfer();
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())
                    ->setOperation(PunchoutGatewayConfig::OPERATION_EDIT)
                    ->addItem(
                        (new PunchoutItemTransfer())
                            ->setSupplierPartId('SKU-123')
                            ->setQuantity('2')
                            ->setUnitPrice('1000'),
                    ),
            );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->expandQuote($quoteTransfer, $setupRequestTransfer);

        // Assert
        $this->assertCount(1, $result->getItems());
        $this->assertSame('SKU-123', $result->getItems()->offsetGet(0)->getSku());
    }

    public function testExpandSessionSetsBuyerCookieOperationAndSessionToken(): void
    {
        // Arrange
        $punchoutSessionTransfer = (new PunchoutSessionTransfer())->setPunchoutData(new PunchoutSessionDataTransfer());
        $quoteTransfer = new QuoteTransfer();
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())
                    ->setBuyerCookie('my-buyer-cookie')
                    ->setBrowserFormPostUrl('https://buyer.example.com/return')
                    ->setOperation(PunchoutGatewayConfig::OPERATION_CREATE),
            );
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->resolveSession($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);

        // Assert
        $this->assertSame('my-buyer-cookie', $result->getBuyerCookie());
        $this->assertSame('https://buyer.example.com/return', $result->getBrowserFormPostUrl());
        $this->assertSame(PunchoutGatewayConfig::OPERATION_CREATE, $result->getOperation());
        $this->assertNotNull($result->getValidTo());
        $this->assertNotEmpty($result->getSessionToken());
    }

    public function testExpandResponseSetsStartPageUrlWithSessionToken(): void
    {
        // Arrange
        $sessionToken = bin2hex(random_bytes(16));
        $punchoutSessionTransfer = (new PunchoutSessionTransfer())->setSessionToken($sessionToken);
        $responseTransfer = new PunchoutSetupResponseTransfer();
        $cxmlSetupRequestTransfer = (new PunchoutCxmlSetupRequestTransfer())
            ->setPayloadId('test-payload-id')
            ->setTimestamp('2024-01-01T00:00:00+00:00');
        $plugin = new DefaultCxmlProcessorPlugin();

        // Act
        $result = $plugin->expandResponse($punchoutSessionTransfer, $responseTransfer, $cxmlSetupRequestTransfer);

        // Assert
        $this->assertSame(sprintf('/punchout-cxml-start?session=%s', $sessionToken), $result->getStartPageUrl());
        $this->assertSame('test-payload-id', $result->getPayloadId());
        $this->assertSame('2024-01-01T00:00:00+00:00', $result->getTimestamp());
    }

    protected function buildConnectionWithCxmlConfig(string $requestUrl, string $sharedSecret): PunchoutConnectionTransfer
    {
        $sharedSecret = password_hash($sharedSecret, PASSWORD_DEFAULT);

        return (new PunchoutConnectionTransfer())
            ->setRequestUrl($requestUrl)
            ->setCxmlConfiguration(
                (new PunchoutCxmlConfigurationTransfer())->setSenderSharedSecret($sharedSecret),
            );
    }

    protected function buildCxmlSetupRequest(
        PunchoutConnectionTransfer $connectionTransfer,
        string $requestUrl,
        string $senderSharedSecret,
    ): PunchoutSetupRequestTransfer {
        return (new PunchoutSetupRequestTransfer())
            ->setConnection($connectionTransfer)
            ->setCxmlSetupRequest(
                (new PunchoutCxmlSetupRequestTransfer())
                    ->setSenderIdentity('test-sender')
                    ->setRequestUrl($requestUrl)
                    ->setSenderSharedSecret($senderSharedSecret),
            );
    }

    protected function createCxmlConnection(array $overrides = []): PunchoutConnectionTransfer
    {
        return $this->tester->havePunchoutConnection(array_merge([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'protocol_type' => PunchoutGatewayConfig::PROTOCOL_TYPE_CXML,
            'processor_plugin_class' => DefaultCxmlProcessorPlugin::class,
        ], $overrides));
    }
}
