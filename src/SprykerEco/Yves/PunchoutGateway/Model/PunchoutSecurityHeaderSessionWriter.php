<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Model;

use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Spryker\Client\Session\SessionClientInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\SecurityHeader\SecurityHeaderHelperTrait;

class PunchoutSecurityHeaderSessionWriter implements PunchoutSecurityHeaderSessionWriterInterface
{
    use SecurityHeaderHelperTrait;

    protected const string DIRECTIVE_FORM_ACTION = 'form-action';

    /**
     * @param array<\SprykerEco\Yves\PunchoutGateway\Dependency\Plugin\PunchoutSecurityHeaderExpanderPluginInterface> $securityHeaderExpanderPlugins
     */
    public function __construct(
        protected SessionClientInterface $sessionClient,
        protected array $securityHeaderExpanderPlugins,
    ) {
    }

    public function writeFromResponse(PunchoutSessionStartResponseTransfer $response): void
    {
        $punchoutSession = $response->getQuote()?->getPunchoutSession();

        if (!$punchoutSession) {
            return;
        }

        $origin = $this->extractOrigin($punchoutSession->getBrowserFormPostUrl() ?? '');

        if (!$origin) {
            return;
        }

        $directives = [sprintf('%s %s', static::DIRECTIVE_FORM_ACTION, $origin)];

        if ($punchoutSession->getConnection()?->getAllowIframe()) {
            $directives = $this->addFrameAncestors($directives, $origin);
        }

        foreach ($this->securityHeaderExpanderPlugins as $plugin) {
            if (!$plugin->isApplicable($punchoutSession)) {
                continue;
            }

            $directives = $plugin->expand($directives, $punchoutSession, $origin);
        }

        $this->sessionClient->set(PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT, implode('; ', $directives));
    }

    protected function extractOrigin(string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (!$scheme || !$host) {
            return null;
        }

        $port = parse_url($url, PHP_URL_PORT);

        return $port
            ? sprintf('%s://%s:%d', $scheme, $host, $port)
            : sprintf('%s://%s', $scheme, $host);
    }
}
