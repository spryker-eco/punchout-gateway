<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\PunchoutGateway\ResponseBuilder;

use CXml\Builder;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;
use CXml\Model\Url;
use CXml\Serializer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;

class CxmlResponseBuilder implements CxmlResponseBuilderInterface
{
    protected const string SENDER_USER_AGENT = 'SprykerEco PunchoutGateway';

    public function __construct(protected Serializer $cxmlSerializer)
    {
    }

    public function buildSuccessResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string
    {
        $punchoutSetupResponse = new PunchOutSetupResponse(
            new Url($punchoutSetupResponseTransfer->getStartPageUrlOrFail()),
        );

        $cxml = Builder::create(static::SENDER_USER_AGENT)
            ->payload($punchoutSetupResponse)
            ->build();

        return $this->cxmlSerializer->serialize($cxml);
    }

    public function buildErrorResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string
    {
        $status = new Status(
            (int)$punchoutSetupResponseTransfer->getStatusCodeOrFail(),
            $punchoutSetupResponseTransfer->getStatusTextOrFail(),
            $punchoutSetupResponseTransfer->getErrorMessage(),
        );

        $cxml = Builder::create(static::SENDER_USER_AGENT)
            ->status($status)
            ->build();

        return $this->cxmlSerializer->serialize($cxml);
    }
}
