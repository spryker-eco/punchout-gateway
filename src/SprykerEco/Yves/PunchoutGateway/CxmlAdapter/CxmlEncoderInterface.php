<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\CxmlAdapter;

use CXml\Model\CXml;

interface CxmlEncoderInterface
{
    public function encodeCxml(CXml $cxml): string;

    public function decodeCxml(string $xml): CXml;
}
