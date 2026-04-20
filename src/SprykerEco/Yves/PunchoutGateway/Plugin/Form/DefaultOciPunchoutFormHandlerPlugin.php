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

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class DefaultOciPunchoutFormHandlerPlugin extends AbstractPlugin implements PunchoutFormHandlerPluginInterface
{
    public function isApplicable(QuoteTransfer $quoteTransfer): bool
    {
        return $quoteTransfer->getPunchoutSession()
            ?->getPunchoutData()
            ?->getOciLoginRequest() !== null;
    }

    public function handle(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer
    {
        return $this->getFactory()
            ->createOciFormFieldBuilder()
            ->build($quoteTransfer);
    }
}
