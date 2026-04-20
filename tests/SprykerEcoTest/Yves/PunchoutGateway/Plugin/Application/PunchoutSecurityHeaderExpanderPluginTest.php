<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Yves\PunchoutGateway\Plugin\Application;

use Codeception\Test\Unit;
use Spryker\Client\Session\SessionClientInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Yves\PunchoutGateway\Expander\PunchoutSecurityHeaderExpander;
use SprykerEcoTest\Yves\PunchoutGateway\PunchoutGatewayYvesTester;

/**
 * @group SprykerEcoTest
 * @group Yves
 * @group PunchoutGateway
 * @group Plugin
 * @group Application
 * @group PunchoutSecurityHeaderExpanderPluginTest
 */
class PunchoutSecurityHeaderExpanderPluginTest extends Unit
{
    protected PunchoutGatewayYvesTester $tester;

    public function testExpandReturnsHeadersUnchangedWhenSessionHasNoCspFragment(): void
    {
        // Arrange
        $sessionClientMock = $this->createMock(SessionClientInterface::class);
        $sessionClientMock->method('get')->willReturn(null);
        $expander = new PunchoutSecurityHeaderExpander($sessionClientMock);
        $headers = ['Content-Security-Policy' => "default-src 'self'"];

        // Act
        $result = $expander->expand($headers);

        // Assert
        $this->assertSame($headers, $result);
    }

    public function testExpandSetsCspHeaderWhenNoneExistsAndSessionHasFragment(): void
    {
        // Arrange
        $fragment = 'form-action https://erp.example.com; frame-ancestors https://erp.example.com';
        $sessionClientMock = $this->createMock(SessionClientInterface::class);
        $sessionClientMock->method('get')
            ->with(PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT)
            ->willReturn($fragment);
        $expander = new PunchoutSecurityHeaderExpander($sessionClientMock);

        // Act
        $result = $expander->expand([]);

        // Assert
        $this->assertSame($fragment, $result['Content-Security-Policy']);
    }

    public function testExpandMergesSessionFragmentIntoExistingCspHeader(): void
    {
        // Arrange
        $fragment = 'form-action https://erp.example.com';
        $sessionClientMock = $this->createMock(SessionClientInterface::class);
        $sessionClientMock->method('get')
            ->with(PunchoutGatewayConfig::SESSION_KEY_PUNCHOUT_CSP_FRAGMENT)
            ->willReturn($fragment);
        $expander = new PunchoutSecurityHeaderExpander($sessionClientMock);
        $headers = ['Content-Security-Policy' => "default-src 'self'; form-action 'self'"];

        // Act
        $result = $expander->expand($headers);

        // Assert
        $this->assertStringContainsString("default-src 'self'", $result['Content-Security-Policy']);
        $this->assertStringContainsString("form-action 'self' https://erp.example.com", $result['Content-Security-Policy']);
    }
}
