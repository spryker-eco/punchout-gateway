<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\ResponseBuilder;

use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

interface CxmlResponseBuilderInterface
{
    public function buildSuccessResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string;

    public function buildErrorResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string;
}
