<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\PunchoutGateway\Controller;

use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class OciController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $punchoutLogger = $this->getFactory()->createPunchoutLogger();

        $punchoutLogger->logGenericInfoMessage('Processing OCI session start...');

        $punchoutSetupRequestTransfer = new PunchoutOciLoginRequestTransfer();
        $punchoutSetupRequestTransfer->setFormData($request->request->all());
        $punchoutSetupRequestTransfer->setRequestUrl($request->getPathInfo());
        $punchoutSetupRequestTransfer->setHttpHeaders($request->headers->all());

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

        if (!$sessionStartResponseTransfer->getRedirectUrl()) {
            return new Response('', $this->getFactory()->getConfig()->getErrorResponseHttpCode());
        }

        $redirectUrl = $sessionStartResponseTransfer->getRedirectUrl();

        $punchoutLogger->logGenericInfoMessage('OCI session processed.', [
            'redirectUrl' => $redirectUrl,
        ]);

        return new RedirectResponse($redirectUrl);
    }
}
