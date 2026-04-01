<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway;

use CXml\Model\CXml;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutCxmlProcessorPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getBusinessFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class DefaultCxmlProcessorPlugin extends AbstractPlugin implements PunchoutCxmlProcessorPluginInterface
{
    public function parseCxmlRequest(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        CXml $cxml,
    ): PunchoutCxmlSetupRequestTransfer {
        return $this->getBusinessFactory()
            ->createDefaultCxmlContentParser()
            ->parseCxmlData($cxmlSetupRequestTransfer, $cxml);
    }

    public function authenticate(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): ?PunchoutConnectionTransfer {
        return $this->getBusinessFactory()
            ->createPunchoutCxmlAuthenticator()
            ->authenticateConnection($cxmlSetupRequestTransfer, $connectionTransfer);
    }

    public function resolveCustomer(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?CustomerTransfer {
        return $this->getBusinessFactory()
            ->createCxmlCustomerResolver()
            ->resolveCustomerByEmail($setupRequestTransfer);
    }

    public function resolveQuote(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer {
        return $this->getBusinessFactory()
            ->createCxmlPunchoutQuoteFinder()
            ->resolveQuote($setupRequestTransfer);
    }

    public function expandQuote(
        QuoteTransfer $quoteTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer {
        return $this->getBusinessFactory()
            ->createCxmlPunchoutQuoteExpander()
            ->expand($quoteTransfer, $setupRequestTransfer);
    }

    public function expandSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer {
        return $this->getBusinessFactory()
            ->createCxmlPunchoutSessionExpander()
            ->expand($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);
    }

    public function expandResponse(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupResponseTransfer $responseTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer
    ): PunchoutSetupResponseTransfer {
        $responseTransfer->setPayloadId($punchoutCxmlSetupRequestTransfer->getPayloadId());
        $responseTransfer->setTimestamp($punchoutCxmlSetupRequestTransfer->getTimestamp());
        $responseTransfer->setStartPageUrl(sprintf(PunchoutGatewayConstants::CXML_SESSION_START_URL, $punchoutSessionTransfer->getSessionToken()));

        return $responseTransfer;
    }
}
