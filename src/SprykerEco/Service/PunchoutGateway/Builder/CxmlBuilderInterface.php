<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway\Builder;

use CXml\Model\CXml;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;

interface CxmlBuilderInterface
{
    public function buildCxmlPayload(PunchOutSetupResponse $payload): CXml;

    public function buildCxmlStatus(Status $status): CXml;
}
