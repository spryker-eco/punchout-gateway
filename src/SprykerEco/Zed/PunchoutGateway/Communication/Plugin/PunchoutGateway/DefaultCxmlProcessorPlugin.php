<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

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
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutCxmlProcessorPluginInterface;

/**
 * @api
 *
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getBusinessFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class DefaultCxmlProcessorPlugin extends AbstractPlugin implements PunchoutCxmlProcessorPluginInterface
{
/**
 * {@inheritDoc}
 * - Extracts sender identity, shared secret, buyer cookie, items,
 *   contact, shipping address, extrinsics, and other protocol fields.
 *
 * @api
 */
    public function parseCxmlRequest(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        CXml $cxml,
    ): PunchoutCxmlSetupRequestTransfer {
        return $this->getBusinessFactory()
            ->createDefaultCxmlContentParser()
            ->parseCxmlData($cxmlSetupRequestTransfer, $cxml);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function authenticate(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?PunchoutConnectionTransfer {
        return $this->getBusinessFactory()
            ->createPunchoutCxmlAuthenticator()
            ->authenticateConnection($setupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function resolveCustomer(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?CustomerTransfer {
        return $this->getBusinessFactory()
            ->createCxmlCustomerResolver()
            ->resolveCustomerByEmail($setupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     * - Used to resume an edit session when a matching quote exists.
     *
     * @api
     */
    public function resolveQuote(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer {
        return $this->getBusinessFactory()
            ->createCxmlPunchoutQuoteFinder()
            ->resolveQuote($setupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function expandQuote(
        QuoteTransfer $quoteTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer {
        return $this->getBusinessFactory()
            ->createCxmlPunchoutQuoteExpander()
            ->expand($quoteTransfer, $setupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     * - Sets buyer cookie, browser form post URL, operation, validity, session token, etc.
     *
     * @api
     */
    public function resolveSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer {
        return $this->getBusinessFactory()
            ->createCxmlPunchoutSessionResolver()
            ->resolve($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function expandResponse(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupResponseTransfer $responseTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        return $this->getBusinessFactory()
            ->createCxmlPunchoutResponseExpander()
            ->expand($punchoutSessionTransfer, $responseTransfer, $punchoutCxmlSetupRequestTransfer);
    }
}
