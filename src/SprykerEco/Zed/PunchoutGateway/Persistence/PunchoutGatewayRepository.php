<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use DateTime;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayPersistenceFactory getFactory()
 */
class PunchoutGatewayRepository extends AbstractRepository implements PunchoutGatewayRepositoryInterface
{
    public function findActiveCxmlConnectionBySenderIdentity(string $senderIdentity): ?PunchoutConnectionTransfer
    {
        $punchoutConnectionEntity = $this->getFactory()
            ->createSpyPunchoutConnectionQuery()
            ->filterBySenderIdentity($senderIdentity)
            ->filterByProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_CXML)
            ->filterByIsActive(true)
            ->joinWithSpyStore()
            ->findOne();

        if ($punchoutConnectionEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createPunchoutConnectionMapper()
            ->mapPunchoutConnectionEntityToTransfer($punchoutConnectionEntity, new PunchoutConnectionTransfer());
    }

    public function findActiveOciConnectionByRequestUrl(string $requestUrl): ?PunchoutConnectionTransfer
    {
        $punchoutConnectionEntity = $this->getFactory()
            ->createSpyPunchoutConnectionQuery()
            ->filterByRequestUrl($requestUrl)
            ->filterByProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_OCI)
            ->filterByIsActive(true)
            ->joinWithSpyStore()
            ->findOne();

        if ($punchoutConnectionEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createPunchoutConnectionMapper()
            ->mapPunchoutConnectionEntityToTransfer($punchoutConnectionEntity, new PunchoutConnectionTransfer());
    }

    public function findPunchoutSessionByIdQuote(int $idQuote): ?PunchoutSessionTransfer
    {
        $punchoutSessionEntity = $this->getFactory()
            ->createSpyPunchoutSessionQuery()
            ->filterByFkQuote($idQuote)
            ->findOne();

        if ($punchoutSessionEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createPunchoutSessionMapper()
            ->mapPunchoutSessionEntityToTransfer($punchoutSessionEntity, new PunchoutSessionTransfer());
    }

    public function findPunchoutSessionByBuyerCookie(string $buyerCookie): ?PunchoutSessionTransfer
    {
        $punchoutSessionEntity = $this->getFactory()
            ->createSpyPunchoutSessionQuery()
            ->filterByBuyerCookie($buyerCookie)
            ->findOne();

        if ($punchoutSessionEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createPunchoutSessionMapper()
            ->mapPunchoutSessionEntityToTransfer($punchoutSessionEntity, new PunchoutSessionTransfer());
    }

    public function findActiveCredentialByUsernameAndConnection(string $username, int $idPunchoutConnection): ?PunchoutCredentialTransfer
    {
        $credentialEntity = $this->getFactory()
            ->createSpyPunchoutCredentialQuery()
            ->filterByUsername($username)
            ->filterByFkPunchoutConnection($idPunchoutConnection)
            ->filterByIsActive(true)
            ->findOne();

        if ($credentialEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createPunchoutCredentialMapper()
            ->mapCredentialEntityToTransfer($credentialEntity, new PunchoutCredentialTransfer());
    }

    public function findValidPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer
    {
        /** @var \Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession|null $punchoutSessionEntity */
        $punchoutSessionEntity = $this->getFactory()
            ->createSpyPunchoutSessionQuery()
            ->filterBySessionToken($sessionToken)
            ->filterByValidTo(new DateTime(), Criteria::GREATER_THAN)
            ->useSpyPunchoutConnectionQuery()
                ->filterByIsActive(true)
            ->endUse()
            ->findOne();

        return $this->mapSessionEntityToTransfer($punchoutSessionEntity);
    }

    public function findPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer
    {
        $punchoutSessionEntity = $this->getFactory()
            ->createSpyPunchoutSessionQuery()
            ->filterBySessionToken($sessionToken)
            ->findOne();

        return $this->mapSessionEntityToTransfer($punchoutSessionEntity);
    }

    protected function mapSessionEntityToTransfer(?SpyPunchoutSession $punchoutSessionEntity): ?PunchoutSessionTransfer
    {
        if ($punchoutSessionEntity === null) {
            return null;
        }

        $punchoutSessionTransfer = $this->getFactory()
            ->createPunchoutSessionMapper()
            ->mapPunchoutSessionEntityToTransfer($punchoutSessionEntity, new PunchoutSessionTransfer());

        $punchoutSessionTransfer->setConnection(
            $this->getFactory()
                ->createPunchoutConnectionMapper()
                ->mapPunchoutConnectionEntityToTransfer($punchoutSessionEntity->getSpyPunchoutConnection(), new PunchoutConnectionTransfer()),
        );

        return $punchoutSessionTransfer;
    }
}
