<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Validator;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class CxmlRequestValidator implements CxmlRequestValidatorInterface
{
    protected const array ALLOWED_OPERATIONS = [
        PunchoutGatewayConfig::OPERATION_CREATE,
        PunchoutGatewayConfig::OPERATION_EDIT,
        PunchoutGatewayConfig::OPERATION_INSPECT,
    ];

    public function __construct(protected PunchoutLoggerInterface $punchoutLogger)
    {
    }

    public function validate(PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer): bool
    {
        $operation = $punchoutCxmlSetupRequestTransfer->getOperation();

        if ($operation === null) {
            return true;
        }

        if (!in_array($operation, static::ALLOWED_OPERATIONS, true)) {
            $this->punchoutLogger->logGenericErrorMessage(
                PunchoutGatewayConfig::ERROR_UNSUPPORTED_OPERATION,
                [
                    'operation' => $operation,
                ],
            );

            return false;
        }

        return true;
    }
}
