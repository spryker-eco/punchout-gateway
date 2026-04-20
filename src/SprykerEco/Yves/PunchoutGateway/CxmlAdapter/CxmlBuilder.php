<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\CxmlAdapter;

use CXml\Builder;
use CXml\Model\CXml;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;

class CxmlBuilder implements CxmlBuilderInterface
{
    protected const string SENDER_USER_AGENT = 'SprykerEco PunchoutGateway';

    public function buildCxmlPayload(PunchOutSetupResponse $payload): CXml
    {
        return Builder::create(static::SENDER_USER_AGENT)
            ->payload($payload)
            ->build();
    }

    public function buildCxmlStatus(Status $status): CXml
    {
        return Builder::create(static::SENDER_USER_AGENT)
            ->status($status)
            ->build();
    }
}
