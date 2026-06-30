<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Persistence;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutCredentialPaginationTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepository;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayPersistenceTester;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Persistence
 * @group PunchoutGatewayRepositoryTest
 */
class PunchoutGatewayRepositoryTest extends Unit
{
    /**
     * A non-existent id that stays within the 4-byte INTEGER range of the primary-key
     * columns. PHP_INT_MAX overflows PostgreSQL's `integer` type and makes the lookup
     * statement itself fail instead of returning no rows.
     */
    protected const int NON_EXISTENT_ID = 2147483647;

    protected PunchoutGatewayPersistenceTester $tester;

    public function testGetPunchoutConnectionCollectionReturnsConnectionsForStore(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);

        $criteriaTransfer = (new PunchoutConnectionCriteriaTransfer())
            ->setIdStore($storeTransfer->getIdStoreOrFail());

        $collection = (new PunchoutGatewayRepository())->getPunchoutConnectionCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutConnection(),
            $collection->getPunchoutConnections()->getArrayCopy(),
        );

        $this->assertContains($connectionTransfer->getIdPunchoutConnection(), $ids);
    }

    public function testGetPunchoutConnectionCollectionFiltersInactiveConnections(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => false,
        ]);

        $criteriaTransfer = (new PunchoutConnectionCriteriaTransfer())
            ->setIdStore($storeTransfer->getIdStoreOrFail())
            ->setIsActive(true);

        $collection = (new PunchoutGatewayRepository())->getPunchoutConnectionCollection($criteriaTransfer);

        foreach ($collection->getPunchoutConnections() as $connection) {
            $this->assertTrue($connection->getIsActive());
        }
    }

    public function testGetPunchoutConnectionCollectionFiltersProtocolType(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $cxmlConnection = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'protocol_type' => 'cxml',
        ]);
        $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'protocol_type' => 'oci',
        ]);

        $criteriaTransfer = (new PunchoutConnectionCriteriaTransfer())
            ->setIdStore($storeTransfer->getIdStoreOrFail())
            ->setProtocolType('cxml');

        $collection = (new PunchoutGatewayRepository())->getPunchoutConnectionCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutConnection(),
            $collection->getPunchoutConnections()->getArrayCopy(),
        );

        $this->assertContains($cxmlConnection->getIdPunchoutConnection(), $ids);

        foreach ($collection->getPunchoutConnections() as $connection) {
            $this->assertSame('cxml', $connection->getProtocolType());
        }
    }

    public function testGetPunchoutConnectionCollectionFiltersSearchTerm(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $uniqueName = sprintf('SearchTarget_%s', uniqid());
        $matchingConnection = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'name' => $uniqueName,
            'protocolType' => 'cxml',

        ]);
        $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'name' => sprintf('Other_%s', uniqid()),
            'protocolType' => 'cxml',
        ]);

        $criteriaTransfer = (new PunchoutConnectionCriteriaTransfer())
            ->setSearchTerm($uniqueName);

        $collection = (new PunchoutGatewayRepository())->getPunchoutConnectionCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutConnection(),
            $collection->getPunchoutConnections()->getArrayCopy(),
        );

        $this->assertContains($matchingConnection->getIdPunchoutConnection(), $ids);
    }

    public function testGetPunchoutConnectionCollectionFiltersRequestUrl(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $uniqueUrl = sprintf('https://unique-%s.test/punchout', uniqid());
        $matchingConnection = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'request_url' => $uniqueUrl,
        ]);
        $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);

        $criteriaTransfer = (new PunchoutConnectionCriteriaTransfer())
            ->setRequestUrl($uniqueUrl);

        $collection = (new PunchoutGatewayRepository())->getPunchoutConnectionCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutConnection(),
            $collection->getPunchoutConnections()->getArrayCopy(),
        );

        $this->assertContains($matchingConnection->getIdPunchoutConnection(), $ids);
        $this->assertCount(1, $ids);
    }

    public function testGetPunchoutConnectionCollectionExcludesNotIdConnections(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionToExclude = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $connectionToInclude = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);

        $criteriaTransfer = (new PunchoutConnectionCriteriaTransfer())
            ->setIdStore($storeTransfer->getIdStoreOrFail())
            ->setNotIdConnections([$connectionToExclude->getIdPunchoutConnectionOrFail()]);

        $collection = (new PunchoutGatewayRepository())->getPunchoutConnectionCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutConnection(),
            $collection->getPunchoutConnections()->getArrayCopy(),
        );

        $this->assertNotContains($connectionToExclude->getIdPunchoutConnection(), $ids);
        $this->assertContains($connectionToInclude->getIdPunchoutConnection(), $ids);
    }

    // -------------------------------------------------------------------------
    // findPunchoutConnectionById
    // -------------------------------------------------------------------------

    public function testFindPunchoutConnectionByIdReturnsTransferWhenFound(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);

        $result = (new PunchoutGatewayRepository())->findPunchoutConnectionById(
            $connectionTransfer->getIdPunchoutConnectionOrFail(),
        );

        $this->assertNotNull($result);
        $this->assertSame($connectionTransfer->getIdPunchoutConnection(), $result->getIdPunchoutConnection());
    }

    public function testFindPunchoutConnectionByIdReturnsNullWhenNotFound(): void
    {
        $result = (new PunchoutGatewayRepository())->findPunchoutConnectionById(static::NON_EXISTENT_ID);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // getPunchoutCredentialCollection
    // -------------------------------------------------------------------------

    public function testGetPunchoutCredentialCollectionReturnsCredentialsForConnection(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $credentialTransfer = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
        ]);

        $criteriaTransfer = (new PunchoutCredentialCriteriaTransfer())
            ->setIdPunchoutConnection($connectionTransfer->getIdPunchoutConnectionOrFail());

        $collection = (new PunchoutGatewayRepository())->getPunchoutCredentialCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutCredential(),
            $collection->getPunchoutCredentials()->getArrayCopy(),
        );

        $this->assertContains($credentialTransfer->getIdPunchoutCredential(), $ids);
    }

    public function testGetPunchoutCredentialCollectionRespectsLimit(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);

        $this->tester->havePunchoutCredential(['fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail()]);
        $this->tester->havePunchoutCredential(['fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail()]);
        $this->tester->havePunchoutCredential(['fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail()]);

        $paginationTransfer = (new PunchoutCredentialPaginationTransfer())->setLimit(2);
        $criteriaTransfer = (new PunchoutCredentialCriteriaTransfer())
            ->setIdPunchoutConnection($connectionTransfer->getIdPunchoutConnectionOrFail())
            ->setPagination($paginationTransfer);

        $collection = (new PunchoutGatewayRepository())->getPunchoutCredentialCollection($criteriaTransfer);

        $this->assertCount(2, $collection->getPunchoutCredentials());
    }

    public function testGetPunchoutCredentialCollectionFiltersByUsername(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $idConnection = $connectionTransfer->getIdPunchoutConnectionOrFail();

        $matchingCredential = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $idConnection,
            'username' => 'user-alpha',
        ]);
        $otherCredential = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $idConnection,
            'username' => 'user-beta',
        ]);

        $criteriaTransfer = (new PunchoutCredentialCriteriaTransfer())
            ->setUsername('user-alpha');

        $collection = (new PunchoutGatewayRepository())->getPunchoutCredentialCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutCredential(),
            $collection->getPunchoutCredentials()->getArrayCopy(),
        );

        $this->assertContains($matchingCredential->getIdPunchoutCredential(), $ids);
        $this->assertNotContains($otherCredential->getIdPunchoutCredential(), $ids);
    }

    public function testGetPunchoutCredentialCollectionFiltersByUsernameScopedToConnection(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionA = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStore(), 'protocolType' => 'oci', 'request_url' => 'aaa']);
        $connectionB = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStore(), 'protocolType' => 'oci', 'request_url' => 'bbb']);

        $credentialA = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionA->getIdPunchoutConnectionOrFail(),
            'username' => 'shared-user',
        ]);
        $credentialB = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionB->getIdPunchoutConnectionOrFail(),
            'username' => 'shared-user',
        ]);

        $criteriaTransfer = (new PunchoutCredentialCriteriaTransfer())
            ->setIdPunchoutConnection($connectionA->getIdPunchoutConnectionOrFail())
            ->setUsername('shared-user');

        $collection = (new PunchoutGatewayRepository())->getPunchoutCredentialCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutCredential(),
            $collection->getPunchoutCredentials()->getArrayCopy(),
        );

        $this->assertContains($credentialA->getIdPunchoutCredential(), $ids);
        $this->assertNotContains($credentialB->getIdPunchoutCredential(), $ids);
    }

    public function testGetPunchoutCredentialCollectionExcludesNotIdPunchoutCredentials(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStoreOrFail()]);
        $idConnection = $connectionTransfer->getIdPunchoutConnectionOrFail();

        $excludedCredential = $this->tester->havePunchoutCredential(['fk_punchout_connection' => $idConnection]);
        $includedCredential = $this->tester->havePunchoutCredential(['fk_punchout_connection' => $idConnection]);

        $criteriaTransfer = (new PunchoutCredentialCriteriaTransfer())
            ->setNotIdPunchoutCredentials([$excludedCredential->getIdPunchoutCredentialOrFail()]);

        $collection = (new PunchoutGatewayRepository())->getPunchoutCredentialCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutCredential(),
            $collection->getPunchoutCredentials()->getArrayCopy(),
        );

        $this->assertNotContains($excludedCredential->getIdPunchoutCredential(), $ids);
        $this->assertContains($includedCredential->getIdPunchoutCredential(), $ids);
    }

    public function testGetPunchoutCredentialCollectionReturnsAllWhenConnectionNotSet(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $uniqueUsername = 'unique-user-' . uniqid();
        $credentialTransfer = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'username' => $uniqueUsername,
        ]);

        $criteriaTransfer = (new PunchoutCredentialCriteriaTransfer())
            ->setUsername($uniqueUsername);

        $collection = (new PunchoutGatewayRepository())->getPunchoutCredentialCollection($criteriaTransfer);

        $ids = array_map(
            static fn ($c) => $c->getIdPunchoutCredential(),
            $collection->getPunchoutCredentials()->getArrayCopy(),
        );

        $this->assertContains($credentialTransfer->getIdPunchoutCredential(), $ids);
    }

    // -------------------------------------------------------------------------
    // findPunchoutCredentialById
    // -------------------------------------------------------------------------

    public function testFindPunchoutCredentialByIdReturnsTransferWhenFound(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $credentialTransfer = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
        ]);

        $result = (new PunchoutGatewayRepository())->findPunchoutCredentialById(
            $credentialTransfer->getIdPunchoutCredentialOrFail(),
        );

        $this->assertNotNull($result);
        $this->assertSame($credentialTransfer->getIdPunchoutCredential(), $result->getIdPunchoutCredential());
    }

    public function testFindPunchoutCredentialByIdReturnsNullWhenNotFound(): void
    {
        $result = (new PunchoutGatewayRepository())->findPunchoutCredentialById(static::NON_EXISTENT_ID);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findCxmlConnectionBySenderIdentity
    // -------------------------------------------------------------------------

    public function testFindCxmlConnectionBySenderIdentityReturnsConnectionWhenFound(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $senderIdentity = sprintf('CxmlSender_%s', uniqid());
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'sender_identity' => $senderIdentity,
            'protocol_type' => 'cxml',
        ]);

        $result = (new PunchoutGatewayRepository())->findCxmlConnectionBySenderIdentity($senderIdentity);

        $this->assertNotNull($result);
        $this->assertSame($connectionTransfer->getIdPunchoutConnection(), $result->getIdPunchoutConnection());
    }

    public function testFindCxmlConnectionBySenderIdentityReturnsNullForOciProtocol(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $senderIdentity = sprintf('OciSender_%s', uniqid());
        $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'sender_identity' => $senderIdentity,
            'protocol_type' => 'oci',
        ]);

        $result = (new PunchoutGatewayRepository())->findCxmlConnectionBySenderIdentity($senderIdentity);

        $this->assertNull($result);
    }

    public function testFindCxmlConnectionBySenderIdentityReturnsNullWhenNotFound(): void
    {
        $result = (new PunchoutGatewayRepository())->findCxmlConnectionBySenderIdentity('non-existent-identity-' . uniqid());

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findActiveCredentialByUsernameAndConnection
    // -------------------------------------------------------------------------

    public function testFindActiveCredentialByUsernameAndConnectionReturnsCredentialWhenActive(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $username = sprintf('user_%s', uniqid());
        $credentialTransfer = $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'username' => $username,
            'is_active' => true,
        ]);

        $result = (new PunchoutGatewayRepository())->findActiveCredentialByUsernameAndConnection(
            $username,
            $connectionTransfer->getIdPunchoutConnectionOrFail(),
        );

        $this->assertNotNull($result);
        $this->assertSame($credentialTransfer->getIdPunchoutCredential(), $result->getIdPunchoutCredential());
    }

    public function testFindActiveCredentialByUsernameAndConnectionReturnsNullWhenInactive(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $username = sprintf('user_%s', uniqid());
        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'username' => $username,
            'is_active' => false,
        ]);

        $result = (new PunchoutGatewayRepository())->findActiveCredentialByUsernameAndConnection(
            $username,
            $connectionTransfer->getIdPunchoutConnectionOrFail(),
        );

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findPunchoutSessionByIdQuote
    // -------------------------------------------------------------------------

    public function testFindPunchoutSessionByIdQuoteReturnsSessionWhenConnectionIsActive(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => true,
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $sessionTransfer = $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
        ]);

        $result = (new PunchoutGatewayRepository())->findPunchoutSessionByIdQuote(
            $quoteTransfer->getIdQuoteOrFail(),
        );

        $this->assertNotNull($result);
        $this->assertSame($sessionTransfer->getIdPunchoutSession(), $result->getIdPunchoutSession());
    }

    public function testFindPunchoutSessionByIdQuoteReturnsNullWhenConnectionIsInactive(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => false,
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
        ]);

        $result = (new PunchoutGatewayRepository())->findPunchoutSessionByIdQuote(
            $quoteTransfer->getIdQuoteOrFail(),
        );

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findPunchoutSessionByBuyerCookie
    // -------------------------------------------------------------------------

    public function testFindPunchoutSessionByBuyerCookieReturnsSessionWhenFound(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $buyerCookie = sprintf('cookie_%s', uniqid());
        $sessionTransfer = $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'buyer_cookie' => $buyerCookie,
        ]);

        $result = (new PunchoutGatewayRepository())->findPunchoutSessionByBuyerCookie($buyerCookie);

        $this->assertNotNull($result);
        $this->assertSame($sessionTransfer->getIdPunchoutSession(), $result->getIdPunchoutSession());
    }

    public function testFindPunchoutSessionByBuyerCookieReturnsNullWhenNotFound(): void
    {
        $result = (new PunchoutGatewayRepository())->findPunchoutSessionByBuyerCookie('non-existent-cookie-' . uniqid());

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findValidPunchoutSessionByToken
    // -------------------------------------------------------------------------

    public function testFindValidPunchoutSessionByTokenReturnsSessionWithFutureValidTo(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => true,
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $sessionToken = bin2hex(random_bytes(16));
        $sessionTransfer = $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'session_token' => $sessionToken,
            'valid_to' => new DateTime('+600 seconds'),
        ]);

        $result = (new PunchoutGatewayRepository())->findValidPunchoutSessionByToken($sessionToken);

        $this->assertNotNull($result);
        $this->assertSame($sessionTransfer->getIdPunchoutSession(), $result->getIdPunchoutSession());
    }

    public function testFindValidPunchoutSessionByTokenReturnsNullWhenExpired(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => true,
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $sessionToken = bin2hex(random_bytes(16));
        $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'session_token' => $sessionToken,
            'valid_to' => new DateTime('-1 second'),
        ]);

        $result = (new PunchoutGatewayRepository())->findValidPunchoutSessionByToken($sessionToken);

        $this->assertNull($result);
    }

    public function testFindValidPunchoutSessionByTokenReturnsNullWhenConnectionInactive(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => false,
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $sessionToken = bin2hex(random_bytes(16));
        $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'session_token' => $sessionToken,
            'valid_to' => new DateTime('+600 seconds'),
        ]);

        $result = (new PunchoutGatewayRepository())->findValidPunchoutSessionByToken($sessionToken);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findPunchoutSessionByToken
    // -------------------------------------------------------------------------

    public function testFindPunchoutSessionByTokenReturnsSessionWhenConnectionIsActive(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => true,
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $sessionToken = bin2hex(random_bytes(16));
        $sessionTransfer = $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'session_token' => $sessionToken,
            'valid_to' => new DateTime('-1 second'),
        ]);

        $result = (new PunchoutGatewayRepository())->findPunchoutSessionByToken($sessionToken);

        $this->assertNotNull($result);
        $this->assertSame($sessionTransfer->getIdPunchoutSession(), $result->getIdPunchoutSession());
    }

    public function testFindPunchoutSessionByTokenReturnsNullWhenConnectionIsInactive(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => 'DE']);
        $connectionTransfer = $this->tester->havePunchoutConnection([
            'fk_store' => $storeTransfer->getIdStoreOrFail(),
            'is_active' => false,
        ]);
        $quoteTransfer = $this->tester->havePersistentQuote([QuoteTransfer::STORE => $storeTransfer, QuoteTransfer::CUSTOMER => $this->tester->haveCustomer()]);
        $sessionToken = bin2hex(random_bytes(16));
        $this->tester->havePunchoutSession([
            'fk_quote' => $quoteTransfer->getIdQuoteOrFail(),
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnectionOrFail(),
            'session_token' => $sessionToken,
        ]);

        $result = (new PunchoutGatewayRepository())->findPunchoutSessionByToken($sessionToken);

        $this->assertNull($result);
    }
}
