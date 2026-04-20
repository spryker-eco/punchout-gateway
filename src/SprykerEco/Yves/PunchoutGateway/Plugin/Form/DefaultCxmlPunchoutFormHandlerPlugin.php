<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Plugin\Form;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class DefaultCxmlPunchoutFormHandlerPlugin extends AbstractPlugin implements PunchoutFormHandlerPluginInterface
{
    public function isApplicable(QuoteTransfer $quoteTransfer): bool
    {
        return $quoteTransfer->getPunchoutSession()
            ?->getPunchoutData()
            ?->getCxmlSetupRequest() !== null;
    }

    public function handle(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer
    {
        $actionUrl = $quoteTransfer->getPunchoutSession()?->getBrowserFormPostUrl();

        if (!$actionUrl) {
            return null;
        }

        $cxmlPayload = $this->getFactory()
            ->getPunchoutGatewayService()
            ->buildCxmlPunchoutOrderMessage($quoteTransfer);

        if (!$cxmlPayload) {
            return null;
        }

        return (new PunchoutFormDataTransfer())
            ->setActionUrl($actionUrl)
            ->addField(PunchoutGatewayConfig::CXML_FORM_FIELD_NAME, $cxmlPayload);
    }
}
