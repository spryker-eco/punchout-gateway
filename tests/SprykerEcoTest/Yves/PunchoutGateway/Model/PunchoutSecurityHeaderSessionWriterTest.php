<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Yves\PunchoutGateway\Model;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\Session\SessionClientInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Yves\PunchoutGateway\Model\PunchoutSecurityHeaderSessionWriter;
use SprykerEco\Yves\PunchoutGateway\Plugin\SecurityHeader\DefaultOciSecurityHeaderExpanderPlugin;
use SprykerEcoTest\Yves\PunchoutGateway\PunchoutGatewayYvesTester;

/**
 * @group SprykerEcoTest
 * @group Yves
 * @group PunchoutGateway
 * @group Model
 * @group PunchoutSecurityHeaderSessionWriterTest
 */
class PunchoutSecurityHeaderSessionWriterTest extends Unit
{
    protected const string BROWSER_FORM_POST_URL = 'https://erp.example.com/post';

    protected const string EXPECTED_ORIGIN = 'https://erp.example.com';

    protected PunchoutGatewayYvesTester $tester;

    public function testWriteFromResponseStoresCspFragmentForCxmlWithIframeAllowed(): void
    {
        $sessionClient = $this->createSessionClientMock();
        $sessionClient->expects($this->once())
            ->method('set')
            ->with(
                PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT,
                sprintf('form-action %s; frame-ancestors %s', static::EXPECTED_ORIGIN, static::EXPECTED_ORIGIN),
            );

        $writer = new PunchoutSecurityHeaderSessionWriter($sessionClient, [new DefaultOciSecurityHeaderExpanderPlugin()]);
        $writer->writeFromResponse($this->buildCxmlResponse(allowIframe: true));
    }

    public function testWriteFromResponseStoresCspFragmentForCxmlWithIframeNotAllowed(): void
    {
        $sessionClient = $this->createSessionClientMock();
        $sessionClient->expects($this->once())
            ->method('set')
            ->with(
                PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT,
                sprintf('form-action %s', static::EXPECTED_ORIGIN),
            );

        $writer = new PunchoutSecurityHeaderSessionWriter($sessionClient, [new DefaultOciSecurityHeaderExpanderPlugin()]);
        $writer->writeFromResponse($this->buildCxmlResponse(allowIframe: false));
    }

    public function testWriteFromResponseStoresCspFragmentForOciWithIframeAllowedAndTargetPresent(): void
    {
        $sessionClient = $this->createSessionClientMock();
        $sessionClient->expects($this->once())
            ->method('set')
            ->with(
                PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT,
                // writer + plugin both add frame-ancestors; array_unique deduplicates
                sprintf('form-action %s; frame-ancestors %s', static::EXPECTED_ORIGIN, static::EXPECTED_ORIGIN),
            );

        $writer = new PunchoutSecurityHeaderSessionWriter($sessionClient, [new DefaultOciSecurityHeaderExpanderPlugin()]);
        $writer->writeFromResponse($this->buildOciResponse(allowIframe: true, formData: [PunchoutGatewayConfig::FORM_DATA_FIELD_TARGET => '_top']));
    }

    public function testWriteFromResponseStoresCspFragmentForOciWithIframeAllowedAndNoTarget(): void
    {
        $sessionClient = $this->createSessionClientMock();
        $sessionClient->expects($this->once())
            ->method('set')
            ->with(
                PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT,
                sprintf('form-action %s; frame-ancestors %s', static::EXPECTED_ORIGIN, static::EXPECTED_ORIGIN),
            );

        $writer = new PunchoutSecurityHeaderSessionWriter($sessionClient, [new DefaultOciSecurityHeaderExpanderPlugin()]);
        $writer->writeFromResponse($this->buildOciResponse(allowIframe: true, formData: []));
    }

    public function testWriteFromResponseStoresCspFragmentForOciWithIframeNotAllowedAndTargetPresent(): void
    {
        $sessionClient = $this->createSessionClientMock();
        $sessionClient->expects($this->once())
            ->method('set')
            ->with(
                PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT,
                sprintf('form-action %s; frame-ancestors %s', static::EXPECTED_ORIGIN, static::EXPECTED_ORIGIN),
            );

        $writer = new PunchoutSecurityHeaderSessionWriter($sessionClient, [new DefaultOciSecurityHeaderExpanderPlugin()]);
        $writer->writeFromResponse($this->buildOciResponse(allowIframe: false, formData: [PunchoutGatewayConfig::FORM_DATA_FIELD_TARGET => '_top']));
    }

    public function testWriteFromResponseStoresCspFragmentForOciWithIframeNotAllowedAndNoTarget(): void
    {
        $sessionClient = $this->createSessionClientMock();
        $sessionClient->expects($this->once())
            ->method('set')
            ->with(
                PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT,
                sprintf('form-action %s', static::EXPECTED_ORIGIN),
            );

        $writer = new PunchoutSecurityHeaderSessionWriter($sessionClient, [new DefaultOciSecurityHeaderExpanderPlugin()]);
        $writer->writeFromResponse($this->buildOciResponse(allowIframe: false, formData: []));
    }

    /**
     * @return \Spryker\Client\Session\SessionClientInterface|\SprykerEcoTest\Yves\PunchoutGateway\Model\MockObject
     */
    protected function createSessionClientMock(): SessionClientInterface
    {
        return $this->createMock(SessionClientInterface::class);
    }

    protected function buildCxmlResponse(bool $allowIframe): PunchoutSessionStartResponseTransfer
    {
        $punchoutSession = (new PunchoutSessionTransfer())
            ->setBrowserFormPostUrl(static::BROWSER_FORM_POST_URL)
            ->setConnection((new PunchoutConnectionTransfer())->setAllowIframe($allowIframe));

        return (new PunchoutSessionStartResponseTransfer())
            ->setQuote((new QuoteTransfer())->setPunchoutSession($punchoutSession));
    }

    /**
     * @param array<string, string> $formData
     */
    protected function buildOciResponse(bool $allowIframe, array $formData): PunchoutSessionStartResponseTransfer
    {
        $ociLoginRequest = (new PunchoutOciLoginRequestTransfer())->setFormData($formData);
        $punchoutData = (new PunchoutSessionDataTransfer())->setOciLoginRequest($ociLoginRequest);

        $punchoutSession = (new PunchoutSessionTransfer())
            ->setBrowserFormPostUrl(static::BROWSER_FORM_POST_URL)
            ->setConnection((new PunchoutConnectionTransfer())->setAllowIframe($allowIframe))
            ->setPunchoutData($punchoutData);

        return (new PunchoutSessionStartResponseTransfer())
            ->setQuote((new QuoteTransfer())->setPunchoutSession($punchoutSession));
    }
}
