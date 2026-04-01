<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\PunchoutGateway\Controller;

use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayFactory getFactory()
 */
class CxmlController extends AbstractController
{
    protected const string CONTENT_TYPE_XML = 'text/xml';

    protected const string HEADER_REFERER = 'Referer';

    protected const string QUERY_PARAM_SESSION = 'session';

    public const string DEFAULT_SESSION_START_URL = '/';

    public function setupAction(Request $request): Response
    {
        $punchoutSetupRequestTransfer = new PunchoutCxmlSetupRequestTransfer();
        $punchoutSetupRequestTransfer->setRawXml($request->getContent());
        $punchoutSetupRequestTransfer->setRequestUrl($request->headers->get(static::HEADER_REFERER, ''));
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

            return new Response(
                'Unauthorized',
                $this->getFactory()->getConfig()->getErrorResponseHttpCode(),
            );
        }

        $punchoutLogger->logSessionStarted($sessionStartResponseTransfer);

        $this->getFactory()
            ->createLoginModel()
            ->loginCustomerFromSession($sessionStartResponseTransfer);

        $redirectUrl = $sessionStartResponseTransfer->getRedirectUrl() ?? static::DEFAULT_SESSION_START_URL;

        $punchoutLogger->logGenericInfoMessage('cXML session processed.', [
            'redirectUrl' => $redirectUrl,
        ]);

        return new RedirectResponse($redirectUrl);
    }

    protected function buildResponse(PunchoutSetupResponseTransfer $responseTransfer): Response
    {
        $cxmlResponseBuilder = $this->getFactory()->createCxmlResponseBuilder();

        $content = $responseTransfer->getIsSuccess()
            ? $cxmlResponseBuilder->buildSuccessResponseXml($responseTransfer)
            : $cxmlResponseBuilder->buildErrorResponseXml($responseTransfer);

        $config = $this->getFactory()->getConfig();

        return new Response(
            $content,
            $responseTransfer->getIsSuccess() ? $config->getSuccessResponseHttpCode() : $config->getErrorResponseHttpCode(),
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
