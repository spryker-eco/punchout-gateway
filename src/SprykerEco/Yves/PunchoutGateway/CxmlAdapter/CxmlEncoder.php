<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\CxmlAdapter;

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
