<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Controller;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class CxmlController extends AbstractController
{
    protected const string CONTENT_TYPE_XML = 'text/xml';

    protected const string QUERY_PARAM_SESSION = 'session';

    protected const string FALLBACK_SESSION_START_URL = '/';

    public function setupAction(Request $request): Response
    {
        $punchoutSetupRequestTransfer = new PunchoutCxmlSetupRequestTransfer();
        $punchoutSetupRequestTransfer->setRawXml($request->getContent());
        $punchoutSetupRequestTransfer->setHttpHeaders($request->headers->all());

        $responseTransfer = $this->getFactory()
            ->getPunchoutGatewayClient()
            ->processPunchoutCxmlSetupRequest($punchoutSetupRequestTransfer);

        $responseTransfer = $this->enhanceUrlWithDomain($responseTransfer);

        return $this->buildResponse($responseTransfer);
    }

    public function startAction(Request $request): Response
    {
        $punchoutLogger = $this->getFactory()->createPunchoutLogger();

        $punchoutLogger->logGenericInfoMessage('Processing cXML session start...');

        $sessionToken = $request->query->getString(static::QUERY_PARAM_SESSION);

        $sessionStartRequestTransfer = new PunchoutSessionStartRequestTransfer();
        $sessionStartRequestTransfer->setSessionToken($sessionToken);

        $sessionStartResponseTransfer = $this->getFactory()
            ->getPunchoutGatewayClient()
            ->startPunchoutCxmlSession($sessionStartRequestTransfer);

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

        $punchoutLogger->logGenericInfoMessage('cXML session processed.', [
            'redirectUrl' => $redirectUrl,
        ]);

        return new RedirectResponse($redirectUrl);
    }

    protected function buildResponse(PunchoutSetupResponseTransfer $responseTransfer): Response
    {
        $cxmlResponseBuilder = $this->getFactory()->createCxmlResponseBuilder();

        $config = $this->getFactory()->getConfig();

        if ($responseTransfer->getIsSuccess()) {
            return new Response(
                $cxmlResponseBuilder->buildSuccessResponseXml($responseTransfer),
                Response::HTTP_OK,
                ['Content-Type' => static::CONTENT_TYPE_XML],
            );
        }

        return new Response(
            $cxmlResponseBuilder->buildErrorResponseXml($responseTransfer),
            $config->getErrorResponseHttpCode(),
            ['Content-Type' => static::CONTENT_TYPE_XML],
        );
    }

    protected function enhanceUrlWithDomain(PunchoutSetupResponseTransfer $responseTransfer): PunchoutSetupResponseTransfer
    {
        if (!$responseTransfer->getStartPageUrl()) {
            return $responseTransfer;
        }

        $responseTransfer->setStartPageUrl(
            $this->getFactory()
                ->getConfig()
                ->getYvesBaseUrl() . $responseTransfer->getStartPageUrl(),
        );

        return $responseTransfer;
    }
}
