<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;

interface PunchoutGatewayRepositoryInterface
{
    public function findActiveCxmlConnectionBySenderIdentity(string $senderIdentity): ?PunchoutConnectionTransfer;

    public function findPunchoutSessionByIdQuote(int $idQuote): ?PunchoutSessionTransfer;

    public function findPunchoutSessionByBuyerCookie(string $buyerCookie): ?PunchoutSessionTransfer;

    public function findActiveCredentialByUsername(string $username): ?PunchoutCredentialTransfer;

    public function findActiveOciConnectionByRequestUrl(string $requestUrl): ?PunchoutConnectionTransfer;

    public function findValidPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer;

    public function findPunchoutSessionByToken(string $sessionToken): ?PunchoutSessionTransfer;
}
