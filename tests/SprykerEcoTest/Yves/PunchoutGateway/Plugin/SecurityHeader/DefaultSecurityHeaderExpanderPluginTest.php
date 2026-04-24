<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Yves\PunchoutGateway\Plugin\SecurityHeader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use SprykerEco\Yves\PunchoutGateway\Plugin\SecurityHeader\DefaultSecurityHeaderExpanderPlugin;
use SprykerEcoTest\Yves\PunchoutGateway\PunchoutGatewayYvesTester;

/**
 * @group SprykerEcoTest
 * @group Yves
 * @group PunchoutGateway
 * @group Plugin
 * @group SecurityHeader
 * @group DefaultSecurityHeaderExpanderPluginTest
 */
class DefaultSecurityHeaderExpanderPluginTest extends Unit
{
    protected const string ORIGIN = 'https://erp.example.com';

    protected PunchoutGatewayYvesTester $tester;

    public function testIsApplicableReturnsTrueWhenIframeIsAllowed(): void
    {
        // Arrange
        $plugin = new DefaultSecurityHeaderExpanderPlugin();
        $sessionTransfer = new PunchoutSessionTransfer();
        $sessionTransfer->setAllowIframe(true);

        // Act & Assert
        $this->assertTrue($plugin->isApplicable($sessionTransfer));
    }

    public function testIsApplicableReturnsFalseWhenIframeIsNotAllowed(): void
    {
        // Arrange
        $plugin = new DefaultSecurityHeaderExpanderPlugin();
        $sessionTransfer = new PunchoutSessionTransfer();
        $sessionTransfer->setAllowIframe(false);

        // Act & Assert
        $this->assertFalse($plugin->isApplicable($sessionTransfer));
    }

    public function testExpandAppendsFrameAncestorsDirectiveWhenAllowIframeIsTrue(): void
    {
        // Arrange
        $plugin = new DefaultSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithCxmlRequest();
        $sessionTransfer->setAllowIframe(true);

        // Act
        $result = $plugin->expand([], $sessionTransfer, static::ORIGIN);

        // Assert
        $this->assertContains(sprintf('frame-ancestors %s', static::ORIGIN), $result);
    }

    public function testExpandDoesNotAppendFrameAncestorsWhenAllowIframeFalseAndNoTarget(): void
    {
        // Arrange
        $plugin = new DefaultSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithCxmlRequest();
        $sessionTransfer->setAllowIframe(false);

        // Act
        $result = $plugin->expand(["form-action 'self'"], $sessionTransfer, static::ORIGIN);

        // Assert
        $this->assertSame(["form-action 'self'"], $result);
    }

    public function testExpandDoesNotAppendFrameAncestorsWhenTargetIsEmptyString(): void
    {
        // Arrange
        $plugin = new DefaultSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithCxmlRequest(['~TARGET' => '']);
        $sessionTransfer->setAllowIframe(false);

        // Act
        $result = $plugin->expand([], $sessionTransfer, static::ORIGIN);

        // Assert
        $this->assertSame([], $result);
    }

    protected function buildSessionWithCxmlRequest(): PunchoutSessionTransfer
    {
        $cxmlSetupRequestTransfer = (new PunchoutCxmlSetupRequestTransfer());

        $punchoutData = (new PunchoutSessionDataTransfer())
            ->setCxmlSetupRequest($cxmlSetupRequestTransfer);

        return (new PunchoutSessionTransfer())
            ->setPunchoutData($punchoutData);
    }
}
