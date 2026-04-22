<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Parser;

use CXml\Model\CXml;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;

interface DefaultCxmlContentParserInterface
{
    public function parseCxmlData(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
        CXml $cxml,
    ): PunchoutCxmlSetupRequestTransfer;
}
