<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCollectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;

interface PunchoutGatewayRepositoryInterface
{
    public function getPunchoutConnectionCollection(PunchoutConnectionCriteriaTransfer $criteriaTransfer): PunchoutConnectionCollectionTransfer;

    public function findPunchoutConnectionById(int $idPunchoutConnection): ?PunchoutConnectionTransfer;

    public function getPunchoutCredentialCollection(PunchoutCredentialCriteriaTransfer $criteriaTransfer): PunchoutCredentialCollectionTransfer;

    public function findPunchoutCredentialById(int $idPunchoutCredential): ?PunchoutCredentialTransfer;

    public function findCxmlConnectionBySenderIdentity(string $senderIdentity): ?PunchoutConnectionTransfer;

    public function findPunchoutSessionByIdQuote(int $idQuote): ?PunchoutSessionTransfer;

    public function findPunchoutSessionByBuyerCookie(string $buyerCookie): ?PunchoutSessionTransfer;

    public function findActiveCredentialByUsernameAndConnection(string $username, int $idPunchoutConnection): ?PunchoutCredentialTransfer;

    public function findActiveOciConnectionByRequestUrl(string $requestUrl): ?PunchoutConnectionTransfer;

    public function findValidPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer;

    public function findPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer;
}
