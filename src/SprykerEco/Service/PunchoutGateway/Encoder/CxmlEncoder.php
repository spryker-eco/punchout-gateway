<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway\Encoder;

use CXml\Model\CXml;
use CXml\Serializer;

class CxmlEncoder implements CxmlEncoderInterface
{
    public function __construct(protected Serializer $cxmlSerializer)
    {
    }

    public function encodeCxml(CXml $cxml): string
    {
        return $this->cxmlSerializer->serialize($cxml);
    }

    public function decodeCxml(string $xml): CXml
    {
        return $this->cxmlSerializer->deserialize($xml);
    }
}
