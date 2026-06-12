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

class PunchoutSecurityHeaderSessionWriter implements PunchoutSecurityHeaderSessionWriterInterface
{
    protected const string DIRECTIVE_FORM_ACTION = 'form-action';

    /**
     * @param array<\SprykerEco\Yves\PunchoutGateway\Dependency\Plugin\PunchoutSecurityHeaderExpanderPluginInterface> $securityHeaderExpanderPlugins
     */
    public function __construct(
        protected SessionClientInterface $sessionClient,
        protected array $securityHeaderExpanderPlugins,
    ) {
    }

    public function writeFromResponse(PunchoutSessionStartResponseTransfer $responseTransfer): void
    {
        $punchoutSession = $responseTransfer->getQuote()?->getPunchoutSession();

        if (!$punchoutSession) {
            return;
        }

        $origin = $this->extractOrigin($punchoutSession->getBrowserFormPostUrl() ?? '');

        if (!$origin) {
            return;
        }

        $directives = [sprintf('%s %s', static::DIRECTIVE_FORM_ACTION, $origin)];

        if ($punchoutSession->getConnection()?->getAllowIframe()) {
            $directives[] = sprintf('%s %s', PunchoutGatewayConfig::DIRECTIVE_FRAME_ANCESTORS, $origin);
        }

        foreach ($this->securityHeaderExpanderPlugins as $plugin) {
            $directives = $plugin->expand($directives, $punchoutSession, $origin);
        }

        $directives = array_unique($directives);

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
