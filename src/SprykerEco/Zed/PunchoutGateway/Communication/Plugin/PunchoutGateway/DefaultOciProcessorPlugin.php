<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

/**
 * @api
 *
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getBusinessFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class DefaultOciProcessorPlugin extends AbstractPlugin implements PunchoutProcessorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Uses connection's configured username/password field names to extract credentials from form data.
     * - Verifies credentials against the credential table.
     * - Returns the connection transfer enriched with credential data (e.g., idCustomer), or null on failure.
     *
     * @api
     */
    public function authenticate(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?PunchoutConnectionTransfer {
        return $this->getBusinessFactory()
            ->createPunchoutOciAuthenticator()
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
            ->createOciCustomerResolver()
            ->resolveCustomer($setupRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function resolveQuote(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer {
        return $this->getBusinessFactory()
            ->createOciPunchoutQuoteFinder()
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
        return $quoteTransfer;
    }

    /**
     * {@inheritDoc}
     * - Sets operation, browser form post URL from hook_url form field, etc.
     *
     * @api
     */
    public function resolveSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): ?PunchoutSessionTransfer {
        return $this->getBusinessFactory()
            ->createOciPunchoutSessionResolver()
            ->resolve($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);
    }
}
