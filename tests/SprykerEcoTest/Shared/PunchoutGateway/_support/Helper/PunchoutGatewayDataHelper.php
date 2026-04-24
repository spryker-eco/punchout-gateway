<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Shared\PunchoutGateway\Helper;

use Codeception\Module;
use DateTime;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutCredential;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSessionQuery;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin;
use SprykerEco\Zed\PunchoutGateway\Persistence\Mapper\PunchoutConnectionMapper;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepository;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class PunchoutGatewayDataHelper extends Module
{
    use DataCleanupHelperTrait;
    use LocatorHelperTrait;

    public function havePunchoutConnection(array $seed = []): PunchoutConnectionTransfer
    {
        $idStore = $seed['fk_store'];

        $senderIdentity = $seed['sender_identity'] ?? sprintf('TestIdentity_%s', uniqid());
        $sharedSecret = $seed['shared_secret'] ?? 'test-secret';
        $sharedSecret = password_hash($sharedSecret, PASSWORD_DEFAULT);

        $configuration = $seed['configuration'] ?? json_encode(['senderSharedSecret' => $sharedSecret]);

        $entity = new SpyPunchoutConnection();
        $entity->setName($seed['name'] ?? sprintf('TestConnection_%s', uniqid()));
        $entity->setIsActive($seed['is_active'] ?? true);
        $entity->setProtocolType($seed['protocol_type'] ?? 'cxml');
        $entity->setRequestUrl($seed['request_url'] ?? 'https://test.local/punchout');
        $entity->setSenderIdentity($senderIdentity);
        $entity->setConfiguration($configuration);
        $entity->setProcessorPluginClass($seed['processor_plugin_class'] ?? DefaultCxmlProcessorPlugin::class);
        $entity->setFkStore($idStore);
        $entity->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($entity): void {
            $entity->delete();
        });

        $punchoutConnectionMapper = new PunchoutConnectionMapper($this->getLocator()->utilEncoding()->service());

        return $punchoutConnectionMapper->mapPunchoutConnectionEntityToTransfer($entity, new PunchoutConnectionTransfer());
    }

    public function findPunchoutSessionBySessionToken(string $sessionToken): ?PunchoutSessionTransfer
    {
        $repository = new PunchoutGatewayRepository();

        return $repository->findValidPunchoutSessionByToken($sessionToken);
    }

    public function countPunchoutSessionsByBuyerCookie(string $buyerCookie): int
    {
        return SpyPunchoutSessionQuery::create()
            ->filterByBuyerCookie($buyerCookie)
            ->count();
    }

    public function havePunchoutCredential(array $seed = []): PunchoutCredentialTransfer
    {
        $plainPassword = $seed['password'] ?? 'test-password';

        $entity = new SpyPunchoutCredential();
        $entity->setFkPunchoutConnection($seed['fk_punchout_connection']);
        $entity->setFkCustomer($seed['fk_customer'] ?? null);
        $entity->setUsername($seed['username'] ?? sprintf('TestUser_%s', uniqid()));
        $entity->setPasswordHash(password_hash($plainPassword, PASSWORD_DEFAULT));
        $entity->setIsActive($seed['is_active'] ?? true);
        $entity->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($entity): void {
            $entity->delete();
        });

        $credentialTransfer = new PunchoutCredentialTransfer();
        $credentialTransfer->fromArray($entity->toArray(), true);
        $credentialTransfer->setIdPunchoutCredential($entity->getIdPunchoutCredential());
        $credentialTransfer->setIdPunchoutConnection($entity->getFkPunchoutConnection());
        $credentialTransfer->setIdCustomer($entity->getFkCustomer());

        return $credentialTransfer;
    }

    public function findPunchoutSessionByIdQuote(int $idQuote): ?PunchoutSessionTransfer
    {
        $repository = new PunchoutGatewayRepository();

        return $repository->findPunchoutSessionByIdQuote($idQuote);
    }

    public function havePunchoutSession(array $seed = []): PunchoutSessionTransfer
    {
        $entity = new SpyPunchoutSession();
        $entity->setFkQuote($seed['fk_quote']);
        $entity->setFkPunchoutConnection($seed['fk_punchout_connection']);
        $entity->setFkCustomer($seed['fk_customer'] ?? null);
        $entity->setBuyerCookie($seed['buyer_cookie'] ?? uniqid('cookie_'));
        $entity->setBrowserFormPostUrl($seed['browser_form_post_url'] ?? 'https://test.local/return');
        $entity->setOperation($seed['operation'] ?? 'create');
        $entity->setSessionToken($seed['session_token'] ?? bin2hex(random_bytes(32)));
        $entity->setValidTo($seed['valid_to'] ?? new DateTime('+600 seconds'));
        $entity->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($entity): void {
            $entity->delete();
        });

        $sessionTransfer = new PunchoutSessionTransfer();
        $sessionTransfer->fromArray($entity->toArray(), true);
        $sessionTransfer->setIdPunchoutSession($entity->getIdPunchoutSession());
        $sessionTransfer->setIdQuote($entity->getFkQuote());
        $sessionTransfer->setIdPunchoutConnection($entity->getFkPunchoutConnection());
        $sessionTransfer->setIdCustomer($entity->getFkCustomer());

        return $sessionTransfer;
    }
}
