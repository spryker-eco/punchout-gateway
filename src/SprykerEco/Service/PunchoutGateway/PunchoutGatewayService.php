<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway;

use CXml\Model\CXml;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Service\Kernel\AbstractService;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceFactory getFactory()
 */
class PunchoutGatewayService extends AbstractService implements PunchoutGatewayServiceInterface
{
    public function encodeCxml(CXml $cxml): string
    {
        return $this->getFactory()->createCxmlEncoder()->encodeCxml($cxml);
    }

    public function decodeCxml(string $xml): CXml
    {
        return $this->getFactory()->createCxmlEncoder()->decodeCxml($xml);
    }

    public function buildCxmlPayload(PunchOutSetupResponse $payload): CXml
    {
        return $this->getFactory()->createCxmlBuilder()->buildCxmlPayload($payload);
    }

    public function buildCxmlStatus(Status $status): CXml
    {
        return $this->getFactory()->createCxmlBuilder()->buildCxmlStatus($status);
    }

    public function buildCxmlPunchoutOrderMessage(QuoteTransfer $quoteTransfer): string
    {
        return $this->getFactory()
            ->createCxmlPunchoutOrderMessageMapper()
            ->mapQuoteToCxml($quoteTransfer);
    }
}
