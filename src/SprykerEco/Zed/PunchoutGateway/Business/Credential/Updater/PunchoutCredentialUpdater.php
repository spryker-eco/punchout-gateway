<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Credential\Updater;

use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;

class PunchoutCredentialUpdater implements PunchoutCredentialUpdaterInterface
{
    public function __construct(protected PunchoutGatewayEntityManagerInterface $entityManager)
    {
    }

    public function updatePunchoutCredential(PunchoutCredentialTransfer $punchoutCredentialTransfer): PunchoutCredentialTransfer
    {
        return $this->entityManager->updatePunchoutCredential($punchoutCredentialTransfer);
    }
}
