<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\ResponseBuilder;

use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;
use CXml\Model\Url;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;

class CxmlResponseBuilder implements CxmlResponseBuilderInterface
{
    public function __construct(protected PunchoutGatewayServiceInterface $punchoutGatewayService)
    {
    }

    public function buildSuccessResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string
    {
        $punchoutSetupResponse = new PunchOutSetupResponse(
            new Url($punchoutSetupResponseTransfer->getStartPageUrlOrFail()),
        );

        $cxml = $this->punchoutGatewayService->buildCxmlPayload($punchoutSetupResponse);

        return $this->punchoutGatewayService->encodeCxml($cxml);
    }

    public function buildErrorResponseXml(PunchoutSetupResponseTransfer $punchoutSetupResponseTransfer): string
    {
        $status = new Status(
            (int)$punchoutSetupResponseTransfer->getStatusCodeOrFail(),
            $punchoutSetupResponseTransfer->getStatusTextOrFail(),
            $punchoutSetupResponseTransfer->getErrorMessage(),
        );

        $cxml = $this->punchoutGatewayService->buildCxmlStatus($status);

        return $this->punchoutGatewayService->encodeCxml($cxml);
    }
}
