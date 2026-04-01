<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Session;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

class OciPunchoutSessionExpander implements OciPunchoutSessionExpanderInterface
{
    public function expand(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): PunchoutSessionTransfer {
        $punchoutSessionTransfer->setOperation(PunchoutGatewayConstants::OPERATION_CREATE);
        $punchoutSessionTransfer->setBrowserFormPostUrl($setupRequestTransfer->getOciLoginRequest()->getFormData()[PunchoutGatewayConstants::OCI_DEFAULT_HOOK_URL_FIELD]);

        return $punchoutSessionTransfer;
    }
}
