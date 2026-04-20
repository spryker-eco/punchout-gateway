<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Controller;

use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class OciController extends AbstractController
{
    protected const string FALLBACK_SESSION_START_URL = '/';

    public function indexAction(Request $request): Response
    {
        $punchoutLogger = $this->getFactory()->createPunchoutLogger();

        $punchoutLogger->logGenericInfoMessage('Processing OCI session start...');

        $punchoutSetupRequestTransfer = new PunchoutOciLoginRequestTransfer();
        $punchoutSetupRequestTransfer->setFormData($request->request->all());
        $punchoutSetupRequestTransfer->setRequestUrl($request->getPathInfo());

        $sessionStartResponseTransfer = $this->getFactory()
            ->getPunchoutGatewayClient()
            ->processPunchoutOciStartRequest($punchoutSetupRequestTransfer);

        if (!$sessionStartResponseTransfer->getIsSuccess() || !$sessionStartResponseTransfer->getCustomer()) {
            $punchoutLogger->logSessionStartFailed($sessionStartResponseTransfer);

            return new Response('', $this->getFactory()->getConfig()->getErrorResponseHttpCode());
        }

        $punchoutLogger->logSessionStarted($sessionStartResponseTransfer);

        $this->getFactory()
            ->createLoginModel()
            ->loginCustomerFromSession($sessionStartResponseTransfer);

        $this->getFactory()
            ->createPunchoutSecurityHeaderSessionWriter()
            ->writeFromResponse($sessionStartResponseTransfer);

        $redirectUrl = $sessionStartResponseTransfer->getRedirectUrl() ?? static::FALLBACK_SESSION_START_URL;

        $punchoutLogger->logGenericInfoMessage('OCI session processed.', [
            'redirectUrl' => $redirectUrl,
        ]);

        return new RedirectResponse($redirectUrl);
    }
}
