<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Credential\Deleter;

interface PunchoutCredentialDeleterInterface
{
    public function deletePunchoutCredential(int $idPunchoutCredential): void;
}
