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
