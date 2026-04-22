<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayBusinessTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Business
 * @group ProcessPunchoutOciLoginRequestTest
 */
class ProcessPunchoutOciLoginRequestTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayBusinessTester $tester;

    protected StoreTransfer $storeTransfer;

    protected function _before(): void
    {
        $this->storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
    }

    public function testProcessOciLoginRequestWithValidCredentialsReturnsSuccessfulResponse(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());
        $username = sprintf('TestUser_%s', uniqid());
        $password = 'test-password';
        $hookUrl = 'https://buyer.example.com/punchout/return';

        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createOciConnection(['request_url' => $requestUrl]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'username' => $username,
            'password' => $password,
        ]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'USERNAME' => $username,
                'PASSWORD' => $password,
                'HOOK_URL' => $hookUrl,
            ]),
        );

        $this->assertTrue($responseTransfer->getIsSuccess(), $responseTransfer->getErrorMessage() ?? '');
        $this->assertSame('/', $responseTransfer->getRedirectUrl());
        $this->assertNotNull($responseTransfer->getCustomer());
        $this->assertSame($customerTransfer->getIdCustomer(), $responseTransfer->getCustomer()->getIdCustomer());
        $this->assertNotNull($responseTransfer->getQuote());
        $this->assertNotNull($responseTransfer->getQuote()->getIdQuote());

        $sessionTransfer = $this->tester->findPunchoutSessionByIdQuote($responseTransfer->getQuote()->getIdQuote());

        $this->assertNotNull($sessionTransfer);
        $this->assertSame('create', $sessionTransfer->getOperation());
        $this->assertSame($hookUrl, $sessionTransfer->getBrowserFormPostUrl());
        $this->assertNotNull($sessionTransfer->getPunchoutData()->getOciLoginRequest());
        $this->assertSame($requestUrl, $sessionTransfer->getPunchoutData()->getOciLoginRequest()->getRequestUrl());
        $this->assertSame($hookUrl, $sessionTransfer->getPunchoutData()->getOciLoginRequest()->getFormData()['HOOK_URL']);
    }

    public function testProcessOciLoginRequestWithValidCredentialsReturnsEmptyQuote(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());
        $username = sprintf('TestUser_%s', uniqid());
        $password = 'test-password';

        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createOciConnection(['request_url' => $requestUrl]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'username' => $username,
            'password' => $password,
        ]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'USERNAME' => $username,
                'PASSWORD' => $password,
                'HOOK_URL' => 'https://buyer.example.com/punchout/return',
            ]),
        );

        $this->assertTrue($responseTransfer->getIsSuccess(), $responseTransfer->getErrorMessage() ?? '');
        $this->assertNotNull($responseTransfer->getQuote());
        $this->assertCount(0, $responseTransfer->getQuote()->getItems());
    }

    public function testProcessOciLoginRequestWithNoMatchingConnectionReturnsError(): void
    {
        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer('https://nonexistent.example.com/oci'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('No active connection was found.', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithInactiveConnectionReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());

        $this->createOciConnection(['request_url' => $requestUrl, 'is_active' => false]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('No active connection was found.', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithWrongRequestUrlReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());

        $this->createOciConnection(['request_url' => $requestUrl]);

        // OCI uses exact URL match — any deviation fails
        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl . '/extra'),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('No active connection was found.', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithMissingUsernameInFormDataReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());
        $connectionTransfer = $this->createOciConnection(['request_url' => $requestUrl]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
        ]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'PASSWORD' => 'test-password',
                'HOOK_URL' => 'https://buyer.example.com/return',
            ]),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Authentication failed', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithMissingPasswordInFormDataReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());
        $username = sprintf('TestUser_%s', uniqid());
        $connectionTransfer = $this->createOciConnection(['request_url' => $requestUrl]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'username' => $username,
        ]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'USERNAME' => $username,
                'HOOK_URL' => 'https://buyer.example.com/return',
            ]),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Authentication failed', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithNonExistentUsernameReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());

        $this->createOciConnection(['request_url' => $requestUrl]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'USERNAME' => sprintf('NoSuchUser_%s', uniqid()),
                'PASSWORD' => 'test-password',
                'HOOK_URL' => 'https://buyer.example.com/return',
            ]),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Authentication failed', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithWrongPasswordReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());
        $username = sprintf('TestUser_%s', uniqid());
        $connectionTransfer = $this->createOciConnection(['request_url' => $requestUrl]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'username' => $username,
            'password' => 'correct-password',
        ]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'USERNAME' => $username,
                'PASSWORD' => 'wrong-password',
                'HOOK_URL' => 'https://buyer.example.com/return',
            ]),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Authentication failed', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithCredentialBelongingToDifferentConnectionReturnsError(): void
    {
        $requestUrlA = sprintf('https://test.local/oci-a/%s', uniqid());
        $requestUrlB = sprintf('https://test.local/oci-b/%s', uniqid());
        $username = sprintf('TestUser_%s', uniqid());
        $password = 'test-password';

        $connectionTransferA = $this->createOciConnection(['request_url' => $requestUrlA]);
        $this->createOciConnection(['request_url' => $requestUrlB]);

        // Credential linked to connection A only
        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransferA->getIdPunchoutConnection(),
            'username' => $username,
            'password' => $password,
        ]);

        // Request goes to connection B, but credential belongs to A
        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrlB, [
                'USERNAME' => $username,
                'PASSWORD' => $password,
                'HOOK_URL' => 'https://buyer.example.com/return',
            ]),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Authentication failed', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithMissingHookUrlReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());
        $username = sprintf('TestUser_%s', uniqid());
        $password = 'test-password';

        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createOciConnection(['request_url' => $requestUrl]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'username' => $username,
            'password' => $password,
        ]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'USERNAME' => $username,
                'PASSWORD' => $password,
            ]),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Session creation failed.', $responseTransfer->getErrorMessage());
    }

    public function testProcessOciLoginRequestWithNonHttpsHookUrlReturnsError(): void
    {
        $requestUrl = sprintf('https://test.local/oci/%s', uniqid());
        $username = sprintf('TestUser_%s', uniqid());
        $password = 'test-password';

        $customerTransfer = $this->tester->haveConfirmedCustomer(['storeName' => $this->storeTransfer->getName()]);
        $connectionTransfer = $this->createOciConnection(['request_url' => $requestUrl]);

        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'fk_customer' => $customerTransfer->getIdCustomer(),
            'username' => $username,
            'password' => $password,
        ]);

        $responseTransfer = $this->tester->getFacade()->processPunchoutOciLoginRequest(
            $this->buildOciLoginRequestTransfer($requestUrl, [
                'USERNAME' => $username,
                'PASSWORD' => $password,
                'HOOK_URL' => 'http://buyer.example.com/return',
            ]),
        );

        $this->assertFalse($responseTransfer->getIsSuccess());
        $this->assertSame('Session creation failed.', $responseTransfer->getErrorMessage());
    }

    protected function buildOciLoginRequestTransfer(string $requestUrl, array $formData = []): PunchoutOciLoginRequestTransfer
    {
        return (new PunchoutOciLoginRequestTransfer())
            ->setRequestUrl($requestUrl)
            ->setFormData($formData);
    }

    protected function createOciConnection(array $overrides = []): PunchoutConnectionTransfer
    {
        return $this->tester->havePunchoutConnection(array_merge([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'protocol_type' => 'oci',
            'processor_plugin_class' => DefaultOciProcessorPlugin::class,
        ], $overrides));
    }
}
