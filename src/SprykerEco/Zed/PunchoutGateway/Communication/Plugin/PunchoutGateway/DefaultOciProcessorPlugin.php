<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutOciProcessorPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getBusinessFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class DefaultOciProcessorPlugin extends AbstractPlugin implements PunchoutOciProcessorPluginInterface
{
    public function authenticate(
        PunchoutOciLoginRequestTransfer $ociLoginRequestTransfer,
        PunchoutConnectionTransfer $connectionTransfer,
    ): ?PunchoutConnectionTransfer {
        return $this->getBusinessFactory()
            ->createPunchoutOciAuthenticator()
            ->authenticateConnection($ociLoginRequestTransfer, $connectionTransfer);
    }

    public function resolveCustomer(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): ?CustomerTransfer {
        return $this->getBusinessFactory()
            ->createOciCustomerResolver()
            ->resolveCustomer($setupRequestTransfer);
    }

    public function resolveQuote(
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer {
        return new QuoteTransfer();
    }

    public function expandQuote(
        QuoteTransfer $quoteTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
    ): QuoteTransfer {
        return $quoteTransfer;
    }

    public function expandSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer {
        return $this->getBusinessFactory()
            ->createOciPunchoutSessionExpander()
            ->expand($punchoutSessionTransfer, $setupRequestTransfer, $quoteTransfer);
    }
}
