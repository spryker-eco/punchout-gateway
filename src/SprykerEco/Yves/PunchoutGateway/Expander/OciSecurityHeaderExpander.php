<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Expander;

use Generated\Shared\Transfer\PunchoutSessionTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class OciSecurityHeaderExpander implements OciSecurityHeaderExpanderInterface
{
    public function expand(array $directives, PunchoutSessionTransfer $punchoutSessionTransfer, string $origin): array
    {
        if (!$this->needsFrameAncestors($punchoutSessionTransfer)) {
            return $directives;
        }

        $directives[] = sprintf('%s %s', PunchoutGatewayConfig::DIRECTIVE_FRAME_ANCESTORS, $origin);

        return $directives;
    }

    protected function needsFrameAncestors(PunchoutSessionTransfer $punchoutSessionTransfer): bool
    {
        $formData = $punchoutSessionTransfer->getPunchoutData()?->getOciLoginRequest()?->getFormData() ?? [];

        return !empty($formData[PunchoutGatewayConfig::FORM_DATA_FIELD_TARGET]);
    }
}
