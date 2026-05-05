<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCollectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface getEntityManager()
 */
class PunchoutGatewayFacade extends AbstractFacade implements PunchoutGatewayFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createPunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer
    {
        return $this->getFactory()
            ->createPunchoutConnectionCreator()
            ->createPunchoutConnection($punchoutConnectionTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updatePunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): PunchoutConnectionTransfer
    {
        return $this->getFactory()
            ->createPunchoutConnectionUpdater()
            ->updatePunchoutConnection($punchoutConnectionTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function deletePunchoutConnection(int $idPunchoutConnection): bool
    {
        return $this->getFactory()
            ->createPunchoutConnectionDeleter()
            ->deletePunchoutConnection($idPunchoutConnection);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findPunchoutConnectionById(int $idPunchoutConnection): ?PunchoutConnectionTransfer
    {
        return $this->getFactory()
            ->createPunchoutConnectionReader()
            ->findPunchoutConnectionById($idPunchoutConnection);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPunchoutConnectionCollection(PunchoutConnectionCriteriaTransfer $criteriaTransfer): PunchoutConnectionCollectionTransfer
    {
        return $this->getFactory()
            ->createPunchoutConnectionReader()
            ->getPunchoutConnectionCollection($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createPunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer
    {
        return $this->getFactory()
            ->createPunchoutCredentialCreator()
            ->createPunchoutCredential($punchoutCredentialTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updatePunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer
    {
        return $this->getFactory()
            ->createPunchoutCredentialUpdater()
            ->updatePunchoutCredential($punchoutCredentialTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function deletePunchoutCredential(int $idPunchoutCredential): void
    {
        $this->getFactory()
            ->createPunchoutCredentialDeleter()
            ->deletePunchoutCredential($idPunchoutCredential);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findPunchoutCredentialById(int $idPunchoutCredential): ?PunchoutCredentialTransfer
    {
        return $this->getFactory()
            ->createPunchoutCredentialReader()
            ->findPunchoutCredentialById($idPunchoutCredential);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPunchoutCredentialCollection(PunchoutCredentialCriteriaTransfer $criteriaTransfer): PunchoutCredentialCollectionTransfer
    {
        return $this->getFactory()
            ->createPunchoutCredentialReader()
            ->getPunchoutCredentialCollection($criteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function processPunchoutCxmlSetupRequest(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        return $this->getFactory()
            ->createPunchoutCxmlSetupRequestProcessor()
            ->processSetupRequest($punchoutSetupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function startPunchoutCxmlSession(
        PunchoutSessionStartRequestTransfer $sessionStartRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        return $this->getFactory()
            ->createPunchoutCxmlSessionStarter()
            ->startSession($sessionStartRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function processPunchoutOciLoginRequest(
        PunchoutOciLoginRequestTransfer $punchoutOciLoginRequestTransfer,
    ): PunchoutSessionStartResponseTransfer {
        return $this->getFactory()
            ->createPunchoutOciLoginProcessor()
            ->processLoginRequest($punchoutOciLoginRequestTransfer);
    }
}
