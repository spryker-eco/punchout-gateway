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
use Generated\Shared\Transfer\PunchoutFormDataTransfer;
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

    /**
     * Specification:
     * - Returns all possible source field paths from all registered field mapper plugins.
     * - Each string is a dot-separated path prefixed with the plugin key (e.g. "item.sku", "quote.customer.email").
     *
     * @api
     *
     * @return array<string>
     */
    public function getSourceFieldSuggestions(): array;

    /**
     * Specification:
     * - Returns the list of supported cXML field keys available for mapping.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedCxmlFields(): array;

    /**
     * Specification:
     * - Returns the list of supported OCI field keys available for mapping.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedOciFields(): array;

    /**
     * Specification:
     * - Builds the OCI form data transfer from the quote, applying per-connection field-map overrides.
     * - Returns null when the quote has no punchout session, no browser form post URL, or no OCI login request.
     *
     * @api
     */
    public function mapOciFormData(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer;
}
