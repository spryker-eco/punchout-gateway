<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
