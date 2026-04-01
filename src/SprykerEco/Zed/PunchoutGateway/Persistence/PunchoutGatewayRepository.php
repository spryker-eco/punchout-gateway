<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use DateTime;
use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionConditionsTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnectionQuery;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSession;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayPersistenceFactory getFactory()
 */
class PunchoutGatewayRepository extends AbstractRepository implements PunchoutGatewayRepositoryInterface
{
    public function getPunchoutConnectionCollection(
        PunchoutConnectionCriteriaTransfer $punchoutConnectionCriteriaTransfer,
    ): PunchoutConnectionCollectionTransfer {
        $punchoutConnectionQuery = $this->getFactory()
            ->createSpyPunchoutConnectionQuery();

        $punchoutConnectionQuery = $this->applyPunchoutConnectionConditions(
            $punchoutConnectionQuery,
            $punchoutConnectionCriteriaTransfer->getPunchoutConnectionConditions(),
        );

        return $this->getFactory()
            ->createPunchoutConnectionMapper()
            ->mapPunchoutConnectionEntitiesToPunchoutConnectionCollectionTransfer(
                $punchoutConnectionQuery->find(),
                new PunchoutConnectionCollectionTransfer(),
            );
    }

    public function findActiveCxmlConnectionBySenderIdentity(string $senderIdentity): ?PunchoutConnectionTransfer
    {
        $punchoutConnectionEntity = $this->getFactory()
            ->createSpyPunchoutConnectionQuery()
            ->filterBySenderIdentity($senderIdentity)
            ->filterByProtocolType(PunchoutGatewayConstants::PROTOCOL_TYPE_CXML)
            ->filterByIsActive(true)
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
            ->filterByProtocolType(PunchoutGatewayConstants::PROTOCOL_TYPE_OCI)
            ->filterByIsActive(true)
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

    public function findActiveCredentialByUsername(string $username): ?PunchoutCredentialTransfer
    {
        $credentialEntity = $this->getFactory()
            ->createSpyPunchoutCredentialQuery()
            ->filterByUsername($username)
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
        $punchoutSessionEntity = $this->getFactory()
            ->createSpyPunchoutSessionQuery()
            ->filterBySessionToken($sessionToken)
            ->filterByValidTo(new DateTime(), Criteria::GREATER_THAN)
            ->useSpyPunchoutConnectionQuery()
                ->filterByIsActive(true)
            ->endUse()
            ->findOne();

        return $this->handleSessionEntity($punchoutSessionEntity);
    }

    public function findPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer
    {
        $punchoutSessionEntity = $this->getFactory()
            ->createSpyPunchoutSessionQuery()
            ->filterBySessionToken($sessionToken)
            ->findOne();

        return $this->handleSessionEntity($punchoutSessionEntity);
    }

    protected function handleSessionEntity(?SpyPunchoutSession $punchoutSessionEntity): ?PunchoutSessionTransfer
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

    protected function applyPunchoutConnectionConditions(
        SpyPunchoutConnectionQuery $punchoutConnectionQuery,
        ?PunchoutConnectionConditionsTransfer $punchoutConnectionConditionsTransfer,
    ): SpyPunchoutConnectionQuery {
        if ($punchoutConnectionConditionsTransfer === null) {
            return $punchoutConnectionQuery;
        }

        if (count($punchoutConnectionConditionsTransfer->getPunchoutConnectionIds()) > 0) {
            $punchoutConnectionQuery->filterByIdPunchoutConnection_In(
                $punchoutConnectionConditionsTransfer->getPunchoutConnectionIds(),
            );
        }

        if ($punchoutConnectionConditionsTransfer->getFkStores()) {
            $punchoutConnectionQuery->filterByFkStore_In(
                $punchoutConnectionConditionsTransfer->getFkStores(),
            );
        }

        if (count($punchoutConnectionConditionsTransfer->getProtocolTypes()) > 0) {
            $punchoutConnectionQuery->filterByProtocolType_In(
                $punchoutConnectionConditionsTransfer->getProtocolTypes(),
            );
        }

        if ($punchoutConnectionConditionsTransfer->getSenderIdentities()) {
            $punchoutConnectionQuery->filterBySenderIdentity_In(
                $punchoutConnectionConditionsTransfer->getSenderIdentities(),
            );
        }

        if ($punchoutConnectionConditionsTransfer->getIsActive() !== null) {
            $punchoutConnectionQuery->filterByIsActive(
                $punchoutConnectionConditionsTransfer->getIsActive(),
            );
        }

        return $punchoutConnectionQuery;
    }
}
