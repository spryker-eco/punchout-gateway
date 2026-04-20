<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayPersistenceFactory getFactory()
 */
class PunchoutGatewayEntityManager extends AbstractEntityManager implements PunchoutGatewayEntityManagerInterface
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

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

        $data = $punchoutSessionTransfer->toArray();
        $punchoutSessionEntity->fromArray($data);

        $punchoutSessionEntity->setFkQuote($punchoutSessionTransfer->getIdQuote());
        $punchoutSessionEntity->setFkPunchoutConnection($punchoutSessionTransfer->getIdPunchoutConnection());
        $punchoutSessionEntity->setFkCustomer($punchoutSessionTransfer->getIdCustomer());
        $sessionDataTransfer = $punchoutSessionTransfer->getPunchoutData();
        if ($sessionDataTransfer !== null) {
            $punchoutSessionEntity->setSessionData($this->getFactory()->getServiceUtilEncoding()->encodeJson($sessionDataTransfer->modifiedToArray()) ?? '[]');
        }

        $punchoutSessionEntity->save();

        $punchoutSessionTransfer->setIdPunchoutSession($punchoutSessionEntity->getIdPunchoutSession());
        $punchoutSessionTransfer->setCreatedAt($punchoutSessionEntity->getCreatedAt()?->format(static::DATE_TIME_FORMAT));
        $punchoutSessionTransfer->setUpdatedAt($punchoutSessionEntity->getUpdatedAt()?->format(static::DATE_TIME_FORMAT));

        return $punchoutSessionTransfer;
    }
}
