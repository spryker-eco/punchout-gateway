<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Plugin\SecurityHeader;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\SecurityHeader\SecurityHeaderHelperTrait;
use SprykerEco\Yves\PunchoutGateway\Dependency\Plugin\PunchoutSecurityHeaderExpanderPluginInterface;

class DefaultOciSecurityHeaderExpanderPlugin implements PunchoutSecurityHeaderExpanderPluginInterface
{
    use SecurityHeaderHelperTrait;

    public function isApplicable(PunchoutSessionTransfer $punchoutSession): bool
    {
        return $punchoutSession->getPunchoutData()?->getOciLoginRequest() !== null && !$punchoutSession->getConnection()?->getAllowIframe();
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string> $directives
     *
     * @return array<string>
     */
    public function expand(array $directives, PunchoutSessionTransfer $punchoutSession, string $origin): array
    {
        if ($this->needsFrameAncestors($punchoutSession)) {
            $directives = $this->addFrameAncestors($directives, $origin);
        }

        return $directives;
    }

    protected function needsFrameAncestors(PunchoutSessionTransfer $punchoutSession): bool
    {
        $formData = $punchoutSession->getPunchoutData()?->getOciLoginRequest()?->getFormData() ?? [];

        return !empty($formData[PunchoutGatewayConfig::FORM_DATA_FIELD_TARGET]);
    }
}
