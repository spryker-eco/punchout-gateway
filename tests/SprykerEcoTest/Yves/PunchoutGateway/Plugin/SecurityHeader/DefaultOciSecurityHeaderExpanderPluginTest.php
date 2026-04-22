<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Yves\PunchoutGateway\Plugin\SecurityHeader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionDataTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use SprykerEco\Yves\PunchoutGateway\Plugin\SecurityHeader\DefaultOciSecurityHeaderExpanderPlugin;
use SprykerEcoTest\Yves\PunchoutGateway\PunchoutGatewayYvesTester;

/**
 * @group SprykerEcoTest
 * @group Yves
 * @group PunchoutGateway
 * @group Plugin
 * @group SecurityHeader
 * @group DefaultOciSecurityHeaderExpanderPluginTest
 */
class DefaultOciSecurityHeaderExpanderPluginTest extends Unit
{
    protected const string ORIGIN = 'https://erp.example.com';

    protected PunchoutGatewayYvesTester $tester;

    public function testIsApplicableReturnsTrueWhenOciLoginRequestIsSet(): void
    {
        // Arrange
        $plugin = new DefaultOciSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithOciRequest();

        // Act & Assert
        $this->assertTrue($plugin->isApplicable($sessionTransfer));
    }

    public function testIsApplicableReturnsFalseWhenOciLoginRequestIsNull(): void
    {
        // Arrange
        $plugin = new DefaultOciSecurityHeaderExpanderPlugin();
        $sessionTransfer = (new PunchoutSessionTransfer())
            ->setPunchoutData(new PunchoutSessionDataTransfer());

        // Act & Assert
        $this->assertFalse($plugin->isApplicable($sessionTransfer));
    }

    public function testIsApplicableReturnsFalseWhenPunchoutDataIsNull(): void
    {
        // Arrange
        $plugin = new DefaultOciSecurityHeaderExpanderPlugin();
        $sessionTransfer = new PunchoutSessionTransfer();

        // Act & Assert
        $this->assertFalse($plugin->isApplicable($sessionTransfer));
    }

    public function testExpandAppendsFrameAncestorsDirectiveWhenAllowIframeIsTrue(): void
    {
        // Arrange
        $plugin = new DefaultOciSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithOciRequest();
        $sessionTransfer->setAllowIframe(true);

        // Act
        $result = $plugin->expand([], $sessionTransfer, static::ORIGIN);

        // Assert
        $this->assertContains(sprintf('frame-ancestors %s', static::ORIGIN), $result);
    }

    public function testExpandAppendsFrameAncestorsDirectiveWhenTargetFormDataIsPresent(): void
    {
        // Arrange
        $plugin = new DefaultOciSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithOciRequest(['~TARGET' => '_blank']);
        $sessionTransfer->setAllowIframe(false);

        // Act
        $result = $plugin->expand(["form-action 'self'"], $sessionTransfer, static::ORIGIN);

        // Assert
        $this->assertContains("form-action 'self'", $result);
        $this->assertContains(sprintf('frame-ancestors %s', static::ORIGIN), $result);
    }

    public function testExpandDoesNotAppendFrameAncestorsWhenAllowIframeFalseAndNoTarget(): void
    {
        // Arrange
        $plugin = new DefaultOciSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithOciRequest();
        $sessionTransfer->setAllowIframe(false);

        // Act
        $result = $plugin->expand(["form-action 'self'"], $sessionTransfer, static::ORIGIN);

        // Assert
        $this->assertSame(["form-action 'self'"], $result);
    }

    public function testExpandDoesNotAppendFrameAncestorsWhenTargetIsEmptyString(): void
    {
        // Arrange
        $plugin = new DefaultOciSecurityHeaderExpanderPlugin();
        $sessionTransfer = $this->buildSessionWithOciRequest(['~TARGET' => '']);
        $sessionTransfer->setAllowIframe(false);

        // Act
        $result = $plugin->expand([], $sessionTransfer, static::ORIGIN);

        // Assert
        $this->assertSame([], $result);
    }

    /**
     * @param array<string, string> $formData
     */
    protected function buildSessionWithOciRequest(array $formData = []): PunchoutSessionTransfer
    {
        $ociLoginRequest = (new PunchoutOciLoginRequestTransfer())
            ->setFormData($formData);

        $punchoutData = (new PunchoutSessionDataTransfer())
            ->setOciLoginRequest($ociLoginRequest);

        return (new PunchoutSessionTransfer())
            ->setPunchoutData($punchoutData);
    }
}
