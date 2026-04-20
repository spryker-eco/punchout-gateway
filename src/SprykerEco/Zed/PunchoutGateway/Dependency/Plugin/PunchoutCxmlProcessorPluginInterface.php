<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Dependency\Plugin;

use CXml\Model\CXml;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

/**
 * Provides extension capabilities to handle Cxml-specific PunchOut flow.
 *
 * @api
 */
interface PunchoutCxmlProcessorPluginInterface extends PunchoutProcessorPluginInterface
{
    /**
     * Specification:
     * - Parses the deserialized cXML object into a structured transfer.
     *
     * @api
     */
    public function parseCxmlRequest(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        CXml $cxml,
    ): PunchoutCxmlSetupRequestTransfer;

    /**
     * Specification:
     * - Expand the response transfer with needed data, including redirect URL.
     *
     * @api
     */
    public function expandResponse(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupResponseTransfer $responseTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer;
}
