<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnection;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutCredential;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayPersistenceFactory getFactory()
 */
class PunchoutGatewayEntityManager extends AbstractEntityManager implements PunchoutGatewayEntityManagerInterface
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public function createPunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer
    {
        $connectionEntity = new SpyPunchoutConnection();

        $this->getFactory()
            ->createPunchoutConnectionMapper()
            ->mapPunchoutConnectionTransferToEntity($punchoutConnectionTransfer, $connectionEntity);

        $connectionEntity->save();

        $punchoutConnectionTransfer->setIdPunchoutConnection($connectionEntity->getIdPunchoutConnection());
        $punchoutConnectionTransfer->setCreatedAt($connectionEntity->getCreatedAt()?->format(static::DATE_TIME_FORMAT));
        $punchoutConnectionTransfer->setUpdatedAt($connectionEntity->getUpdatedAt()?->format(static::DATE_TIME_FORMAT));

        return $punchoutConnectionTransfer;
    }

    public function updatePunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer
    {
        $connectionEntity = $this->getFactory()
            ->createSpyPunchoutConnectionQuery()
            ->filterByIdPunchoutConnection($punchoutConnectionTransfer->getIdPunchoutConnectionOrFail())
            ->findOne();

        if ($connectionEntity === null) {
            return $punchoutConnectionTransfer;
        }

        $this->getFactory()
            ->createPunchoutConnectionMapper()
            ->mapPunchoutConnectionTransferToEntity($punchoutConnectionTransfer, $connectionEntity);

        $connectionEntity->save();

        $punchoutConnectionTransfer->setUpdatedAt($connectionEntity->getUpdatedAt()?->format(static::DATE_TIME_FORMAT));

        return $punchoutConnectionTransfer;
    }

    public function deletePunchoutConnection(int $idPunchoutConnection): bool
    {
        return $this->getFactory()
            ->createSpyPunchoutConnectionQuery()
            ->filterByIdPunchoutConnection($idPunchoutConnection)
            ->delete() > 0;
    }

    public function createPunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer
    {
        $credentialEntity = new SpyPunchoutCredential();

        $this->getFactory()
            ->createPunchoutCredentialMapper()
            ->mapCredentialTransferToEntity($punchoutCredentialTransfer, $credentialEntity);

        $credentialEntity->save();

        $punchoutCredentialTransfer->setIdPunchoutCredential($credentialEntity->getIdPunchoutCredential());

        return $punchoutCredentialTransfer;
    }

    public function updatePunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer
    {
        $credentialEntity = $this->getFactory()
            ->createSpyPunchoutCredentialQuery()
            ->filterByIdPunchoutCredential($punchoutCredentialTransfer->getIdPunchoutCredentialOrFail())
            ->findOne();

        if ($credentialEntity === null) {
            return $punchoutCredentialTransfer;
        }

        $this->getFactory()
            ->createPunchoutCredentialMapper()
            ->mapCredentialTransferToEntity($punchoutCredentialTransfer, $credentialEntity);

        $credentialEntity->save();

        return $punchoutCredentialTransfer;
    }

    public function deletePunchoutCredential(int $idPunchoutCredential): void
    {
        $this->getFactory()
            ->createSpyPunchoutCredentialQuery()
            ->filterByIdPunchoutCredential($idPunchoutCredential)
            ->delete();
    }

    public function deletePunchoutSessionByToken(PunchoutSessionTransfer $punchoutSessionTransfer): int
    {
        $query = $this->getFactory()->createSpyPunchoutSessionQuery();

        $query->filterBySessionToken($punchoutSessionTransfer->getSessionTokenOrFail());

        if ($punchoutSessionTransfer->getIdPunchoutSession()) {
            $query->_or()
                ->filterByIdPunchoutSession($punchoutSessionTransfer->getIdPunchoutSessionOrFail());
        }

        return $query->delete();
    }

    public function deletePunchoutSessionById(PunchoutSessionTransfer $punchoutSessionTransfer): int
    {
        $query = $this->getFactory()->createSpyPunchoutSessionQuery();

        $query->filterByIdPunchoutSession($punchoutSessionTransfer->getIdPunchoutSessionOrFail());

        return $query->delete();
    }

    public function deletePunchoutSessionByBuyerCookie(PunchoutSessionTransfer $punchoutSessionTransfer): int
    {
        $query = $this->getFactory()->createSpyPunchoutSessionQuery();

        $query->filterByBuyerCookie($punchoutSessionTransfer->getBuyerCookieOrFail());

        return $query->delete();
    }

    public function createPunchoutSession(PunchoutSessionTransfer $punchoutSessionTransfer): PunchoutSessionTransfer
    {
        $punchoutSessionEntity = new SpyPunchoutSession();

        $punchoutSessionEntity = $this->getFactory()
            ->createPunchoutSessionMapper()
            ->mapPunchoutSessionTransferToEntity($punchoutSessionTransfer, $punchoutSessionEntity);

        $punchoutSessionEntity->save();

        $punchoutSessionTransfer->setIdPunchoutSession($punchoutSessionEntity->getIdPunchoutSession());
        $punchoutSessionTransfer->setCreatedAt($punchoutSessionEntity->getCreatedAt()?->format(static::DATE_TIME_FORMAT));
        $punchoutSessionTransfer->setUpdatedAt($punchoutSessionEntity->getUpdatedAt()?->format(static::DATE_TIME_FORMAT));

        return $punchoutSessionTransfer;
    }
}
