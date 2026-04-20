<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway\Builder;

use CXml\Builder;
use CXml\Model\CXml;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;

class CxmlBuilder implements CxmlBuilderInterface
{
    public function buildCxmlPayload(PunchOutSetupResponse $payload): CXml
    {
        return Builder::create('')
            ->payload($payload)
            ->build();
    }

    public function buildCxmlStatus(Status $status): CXml
    {
        return Builder::create('')
            ->status($status)
            ->build();
    }
}
