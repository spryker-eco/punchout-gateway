<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\FormBuilder;

use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class CxmlFormFieldBuilder implements CxmlFormFieldBuilderInterface
{
    public function __construct(protected PunchoutGatewayServiceInterface $punchoutGatewayService)
    {
    }

    public function build(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer
    {
        $actionUrl = $quoteTransfer->getPunchoutSession()?->getBrowserFormPostUrl();

        if (!$actionUrl) {
            return null;
        }

        $cxmlPayload = $this->punchoutGatewayService->buildCxmlPunchoutOrderMessage($quoteTransfer);

        if (!$cxmlPayload) {
            return null;
        }

        return (new PunchoutFormDataTransfer())
            ->setActionUrl($actionUrl)
            ->addField(PunchoutGatewayConfig::CXML_FORM_FIELD_NAME, $cxmlPayload);
    }
}
