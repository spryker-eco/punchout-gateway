<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway\Encoder;

use CXml\Model\CXml;

interface CxmlEncoderInterface
{
    public function encodeCxml(CXml $cxml): string;

    public function decodeCxml(string $xml): CXml;
}
