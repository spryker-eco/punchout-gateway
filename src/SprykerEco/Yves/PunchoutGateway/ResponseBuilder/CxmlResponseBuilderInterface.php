<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\PunchoutGateway\ResponseBuilder;

use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

interface CxmlResponseBuilderInterface
{
    /**
     * Builds a cXML success response XML string containing the start page URL.
     */
    public function buildSuccessResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string;

    /**
     * Builds a cXML error response XML string with the given status code and message.
     */
    public function buildErrorResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string;
}
