<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Oci\Session;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class OciPunchoutSessionResolver implements OciPunchoutSessionResolverInterface
{
    protected const string REQUIRED_HOOK_URL_PREFIX = 'https://';

    public function __construct(
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function resolve(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): ?PunchoutSessionTransfer {
        $punchoutSessionTransfer->setOperation(PunchoutGatewayConfig::OPERATION_CREATE);
        $punchoutSessionTransfer->setBrowserFormPostUrl($setupRequestTransfer->getOciLoginRequest()?->getFormData()[PunchoutGatewayConfig::OCI_HOOK_URL_FIELD] ?? null);

        if ($punchoutSessionTransfer->getBrowserFormPostUrl() === null || !str_starts_with($punchoutSessionTransfer->getBrowserFormPostUrl(), static::REQUIRED_HOOK_URL_PREFIX)) {
            $this->punchoutLogger->logGenericErrorMessage('Form data is missing or wrong', [
                PunchoutGatewayConfig::OCI_HOOK_URL_FIELD => $punchoutSessionTransfer->getBrowserFormPostUrl(),
            ]);

            return null;
        }

        $punchoutSessionTransfer->getPunchoutData()->setOciLoginRequest($setupRequestTransfer->getOciLoginRequest());

        return $punchoutSessionTransfer;
    }
}
