<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Validator;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;

interface CxmlRequestValidatorInterface
{
    /**
     * Specification:
     * - Returns true when the operation is null, 'create', 'edit', or 'inspect'.
     * - Returns false for any other operation value.
     *
     * @api
     */
    public function validate(PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer): bool;
}
