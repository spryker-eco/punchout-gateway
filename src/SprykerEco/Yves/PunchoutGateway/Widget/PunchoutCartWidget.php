<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Widget;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Yves\Kernel\Widget\AbstractWidget;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class PunchoutCartWidget extends AbstractWidget
{
    protected const string PARAMETER_FORM_DATA = 'formData';

    public function __construct(QuoteTransfer $quoteTransfer)
    {
        $formData = $this->getFactory()
            ->createPunchoutFormDataBuilder()
            ->build($quoteTransfer);

        $this->addParameter(static::PARAMETER_FORM_DATA, $formData);
    }

    public static function getName(): string
    {
        return 'PunchoutCartWidget';
    }

    public static function getTemplate(): string
    {
        return '@PunchoutGateway/views/punchout-cart/punchout-cart.twig';
    }
}
