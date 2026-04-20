<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Plugin\Oci;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayCommunicationTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group Plugin
 * @group Oci
 * @group DefaultOciProcessorPluginTest
 */
class DefaultOciProcessorPluginTest extends Unit
{
    use LocatorHelperTrait;

    protected const string SPECIAL_USERNAME_FIELD = 'special-usernameField';

    protected const string SPECIAL_PASSWORD_FIELD = 'special-passwordField';

    protected PunchoutGatewayCommunicationTester $tester;

    protected StoreTransfer $storeTransfer;

    protected function _before(): void
    {
        $this->storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
    }

    public function testAuthenticateWithValidCredentialsReturnsConnectionWithCustomer(): void
    {
        // Arrange
        $username = sprintf('TestUser_%s', uniqid());
        $password = 'test-password';
        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createOciConnection();

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'username' => $username,
            'password' => $password,
        ]);

        $setupRequestTransfer = $this->buildSetupRequestTransfer($connectionTransfer, [
            'USERNAME' => $username,
            'PASSWORD' => $password,
        ]);
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->authenticate($setupRequestTransfer);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($customerTransfer->getIdCustomer(), $result->getIdCustomer());
    }

    public function testAuthenticateWithValidCredentialsReturnsConnectionWithCustomerCustomFields(): void
    {
        // Arrange
        $username = sprintf('TestUser_%s', uniqid());
        $password = 'test-password';
        $username2 = sprintf('TestUser2_%s', uniqid());
        $password2 = 'test-password-2';

        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $newConnectionTransfer = $this->createOciConnection([
            'request_url' => 'new-url',
            'configuration' => json_encode([
                'usernameField' => static::SPECIAL_USERNAME_FIELD,
                'passwordField' => static::SPECIAL_PASSWORD_FIELD,
            ]),
        ]);

        $connectionTransfer = $this->createOciConnection();

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $newConnectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'username' => $username,
            'password' => $password,
        ]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'username' => $username2,
            'password' => $password2,
        ]);

        $plugin = new DefaultOciProcessorPlugin();
        $failedSetupRequestTransfer = $this->buildSetupRequestTransfer($newConnectionTransfer, [
            'USERNAME' => $username,
            'PASSWORD' => $password,
        ]);
        $okNewSetupRequestTransfer = $this->buildSetupRequestTransfer($newConnectionTransfer, [
            static::SPECIAL_USERNAME_FIELD => $username,
            static::SPECIAL_PASSWORD_FIELD => $password,
        ]);
        $okSetupRequestTransfer = $this->buildSetupRequestTransfer($connectionTransfer, [
            'USERNAME' => $username2,
            'PASSWORD' => $password2,
        ]);

        // Act
        $failedResult = $plugin->authenticate($failedSetupRequestTransfer);
        $okNewResult = $plugin->authenticate($okNewSetupRequestTransfer);
        $okResult = $plugin->authenticate($okSetupRequestTransfer);

        // Assert
        $this->assertNotNull($okResult);
        $this->assertSame($customerTransfer->getIdCustomer(), $okResult->getIdCustomer());
        $this->assertNotNull($okNewResult);
        $this->assertSame($customerTransfer->getIdCustomer(), $okNewResult->getIdCustomer());

        $this->assertNull($failedResult);
    }

    public function testAuthenticateWithMissingUsernameInFormDataReturnsNull(): void
    {
        // Arrange
        $connectionTransfer = $this->createOciConnection();
        $setupRequestTransfer = $this->buildSetupRequestTransfer($connectionTransfer, [
            'PASSWORD' => 'test-password',
        ]);
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->authenticate($setupRequestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testAuthenticateWithWrongPasswordReturnsNull(): void
    {
        // Arrange
        $username = sprintf('TestUser_%s', uniqid());
        $connectionTransfer = $this->createOciConnection();

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'username' => $username,
            'password' => 'correct-password',
        ]);

        $setupRequestTransfer = $this->buildSetupRequestTransfer($connectionTransfer, [
            'USERNAME' => $username,
            'PASSWORD' => 'wrong-password',
        ]);
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->authenticate($setupRequestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testAuthenticateWithNonExistentUsernameReturnsNull(): void
    {
        // Arrange
        $connectionTransfer = $this->createOciConnection();
        $setupRequestTransfer = $this->buildSetupRequestTransfer($connectionTransfer, [
            'USERNAME' => sprintf('NoSuchUser_%s', uniqid()),
            'PASSWORD' => 'test-password',
        ]);
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->authenticate($setupRequestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testResolveCustomerWithValidIdCustomerReturnsCustomerTransfer(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = (new PunchoutConnectionTransfer())
            ->setIdCustomer($customerTransfer->getIdCustomer());
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())->setConnection($connectionTransfer);
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->resolveCustomer($setupRequestTransfer);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($customerTransfer->getIdCustomer(), $result->getIdCustomer());
    }

    public function testResolveCustomerWithNullIdCustomerReturnsNull(): void
    {
        // Arrange
        $connectionTransfer = new PunchoutConnectionTransfer();
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())->setConnection($connectionTransfer);
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->resolveCustomer($setupRequestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testResolveQuoteReturnsQuoteWithDefaultName(): void
    {
        // Arrange
        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->resolveQuote($setupRequestTransfer);

        // Assert
        $this->assertSame(PunchoutGatewayConfig::DEFAULT_QUOTE_NAME, $result->getName());
    }

    public function testExpandQuoteReturnsQuoteUnchanged(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->setName('my-quote');
        $setupRequestTransfer = new PunchoutSetupRequestTransfer();
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->expandQuote($quoteTransfer, $setupRequestTransfer);

        // Assert
        $this->assertSame('my-quote', $result->getName());
    }

    public function testExpandSessionWithValidHttpsHookUrlReturnsExpandedSession(): void
    {
        // Arrange
        $hookUrl = 'https://buyer.example.com/punchout/return';
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setOciLoginRequest(
                (new PunchoutOciLoginRequestTransfer())->setFormData(['HOOK_URL' => $hookUrl]),
            );
        $punchoutSessionTransfer = (new PunchoutSessionTransfer())->setPunchoutData(new PunchoutSessionDataTransfer());
        $quoteTransfer = new QuoteTransfer();
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->resolveSession($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(PunchoutGatewayConfig::OPERATION_CREATE, $result->getOperation());
        $this->assertSame($hookUrl, $result->getBrowserFormPostUrl());
    }

    public function testExpandSessionWithMissingHookUrlReturnsNull(): void
    {
        // Arrange
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setOciLoginRequest(
                (new PunchoutOciLoginRequestTransfer())->setFormData([]),
            );
        $punchoutSessionTransfer = new PunchoutSessionTransfer();
        $quoteTransfer = new QuoteTransfer();
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->resolveSession($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testExpandSessionWithNonHttpsHookUrlReturnsNull(): void
    {
        // Arrange
        $setupRequestTransfer = (new PunchoutSetupRequestTransfer())
            ->setOciLoginRequest(
                (new PunchoutOciLoginRequestTransfer())->setFormData(['HOOK_URL' => 'http://buyer.example.com/return']),
            );
        $punchoutSessionTransfer = new PunchoutSessionTransfer();
        $quoteTransfer = new QuoteTransfer();
        $plugin = new DefaultOciProcessorPlugin();

        // Act
        $result = $plugin->resolveSession($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);

        // Assert
        $this->assertNull($result);
    }

    protected function buildSetupRequestTransfer(PunchoutConnectionTransfer $connectionTransfer, array $formData = []): PunchoutSetupRequestTransfer
    {
        return (new PunchoutSetupRequestTransfer())
            ->setConnection($connectionTransfer)
            ->setOciLoginRequest(
                (new PunchoutOciLoginRequestTransfer())->setFormData($formData),
            );
    }

    protected function createOciConnection(array $overrides = []): PunchoutConnectionTransfer
    {
        return $this->tester->havePunchoutConnection(array_merge([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'protocol_type' => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
            'processor_plugin_class' => DefaultOciProcessorPlugin::class,
        ], $overrides));
    }
}
