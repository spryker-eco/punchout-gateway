<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\CxmlAdapter;

use CXml\Model\CXml;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;

interface CxmlBuilderInterface
{
    public function buildCxmlPayload(PunchOutSetupResponse $payload): CXml;

    public function buildCxmlStatus(Status $status): CXml;
}
