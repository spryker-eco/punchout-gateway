<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSessionQuery;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayPersistenceFactory getFactory()
 */
class PunchoutGatewayEntityManager extends AbstractEntityManager implements PunchoutGatewayEntityManagerInterface
{
    public function deletePunchoutSessionIfExists(PunchoutSessionTransfer $punchoutSessionTransfer): int
    {
        $query = SpyPunchoutSessionQuery::create();

        $query->filterByBuyerCookie($punchoutSessionTransfer->getBuyerCookieOrFail());

        if ($punchoutSessionTransfer->getIdPunchoutSession()) {
            $query->_or()
                ->filterByIdPunchoutSession($punchoutSessionTransfer->getIdPunchoutSessionOrFail());
        }

        return $query->delete();
    }

    public function createPunchoutSession(PunchoutSessionTransfer $punchoutSessionTransfer): PunchoutSessionTransfer
    {
        $punchoutSessionEntity = new SpyPunchoutSession();
        $data = $punchoutSessionTransfer->toArray();
        unset($data[PunchoutSessionTransfer::EXTRINSICS]);
        $punchoutSessionEntity->fromArray($data);

        $punchoutSessionEntity->setFkQuote($punchoutSessionTransfer->getIdQuote());
        $punchoutSessionEntity->setFkPunchoutConnection($punchoutSessionTransfer->getIdPunchoutConnection());
        $punchoutSessionEntity->setFkCustomer($punchoutSessionTransfer->getIdCustomer());

        $extrinsics = $punchoutSessionTransfer->getExtrinsics();
        if ($extrinsics !== []) {
            $punchoutSessionEntity->setExtrinsics(json_encode($extrinsics));
        }

        $punchoutSessionEntity->save();

        $punchoutSessionTransfer->setIdPunchoutSession($punchoutSessionEntity->getIdPunchoutSession());
        $punchoutSessionTransfer->setCreatedAt($punchoutSessionEntity->getCreatedAt()?->format('Y-m-d H:i:s'));
        $punchoutSessionTransfer->setUpdatedAt($punchoutSessionEntity->getUpdatedAt()?->format('Y-m-d H:i:s'));

        return $punchoutSessionTransfer;
    }
}
