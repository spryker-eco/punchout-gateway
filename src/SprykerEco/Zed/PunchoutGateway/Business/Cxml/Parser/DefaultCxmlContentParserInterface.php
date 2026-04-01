<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business\Cxml\Parser;

use CXml\Model\CXml;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;

interface DefaultCxmlContentParserInterface
{
    public function parseCxmlData(
        PunchoutCxmlSetupRequestTransfer $punchoutSetupRequestTransfer,
        CXml $cxml
    ): PunchoutCxmlSetupRequestTransfer;
}
