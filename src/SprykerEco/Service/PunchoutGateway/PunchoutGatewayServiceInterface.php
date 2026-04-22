<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway;

use CXml\Model\CXml;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;
use Generated\Shared\Transfer\QuoteTransfer;

interface PunchoutGatewayServiceInterface
{
    /**
     * Specification:
     * - Encodes CXml object to XML string.
     *
     * @api
     */
    public function encodeCxml(CXml $cxml): string;

    /**
     * Specification:
     * - Decodes XML string to CXml object.
     *
     * @api
     */
    public function decodeCxml(string $xml): CXml;

    /**
     * Specification:
     * - Created a CXml object with a payload.
     *
     * @api
     */
    public function buildCxmlPayload(PunchOutSetupResponse $payload): CXml;

    /**
     * Specification:
     * - Created a CXml object with a status.
     *
     * @api
     */
    public function buildCxmlStatus(Status $status): CXml;

    /**
     * Specification:
     * - Builds a cXML PunchOutOrderMessage XML string from the given QuoteTransfer.
     * - Uses identities stored on QuoteTransfer.punchoutSession.punchoutData.cxmlSetupRequest (From/To swapped for outbound direction).
     *
     * @api
     */
    public function buildCxmlPunchoutOrderMessage(QuoteTransfer $quoteTransfer): string;
}
