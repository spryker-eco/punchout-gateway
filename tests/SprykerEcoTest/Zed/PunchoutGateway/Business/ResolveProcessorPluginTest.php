<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Business;

use Codeception\Test\Unit;
use CXml\Model\CXml;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\PunchoutSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSetupResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Zed\PunchoutGateway\Business\Exception\WrongProcessorException;
use SprykerEco\Zed\PunchoutGateway\Business\Model\ProcessorPluginResolver;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutCxmlProcessorPluginInterface;
use SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Business
 * @group ResolveProcessorPluginTest
 */
class ResolveProcessorPluginTest extends Unit
{
    public function testResolveProcessorPluginWithBaseInterfaceReturnsPluginInstance(): void
    {
        // Arrange
        $resolver = new ProcessorPluginResolver();
        $connectionTransfer = $this->buildConnection(DummyPunchoutProcessorPlugin::class);

        // Act
        $result = $resolver->resolveProcessorPlugin($connectionTransfer, PunchoutProcessorPluginInterface::class);

        // Assert
        $this->assertInstanceOf(PunchoutProcessorPluginInterface::class, $result);
    }

    public function testResolveProcessorPluginWithCxmlInterfaceReturnsCxmlPluginInstance(): void
    {
        // Arrange
        $resolver = new ProcessorPluginResolver();
        $connectionTransfer = $this->buildConnection(DummyPunchoutCxmlProcessorPlugin::class);

        // Act
        $result = $resolver->resolveProcessorPlugin($connectionTransfer, PunchoutCxmlProcessorPluginInterface::class);

        // Assert
        $this->assertInstanceOf(PunchoutCxmlProcessorPluginInterface::class, $result);
    }

    public function testResolveProcessorPluginThrowsWhenClassDoesNotExist(): void
    {
        // Arrange
        $resolver = new ProcessorPluginResolver();
        $connectionTransfer = $this->buildConnection('NonExistent\\Plugin\\Class');

        // Expect
        $this->expectException(WrongProcessorException::class);
        $this->expectExceptionMessageMatches('/does not exist/');

        // Act
        $resolver->resolveProcessorPlugin($connectionTransfer, PunchoutProcessorPluginInterface::class);
    }

    public function testResolveProcessorPluginThrowsWhenClassDoesNotImplementExpectedBaseInterface(): void
    {
        // Arrange
        $resolver = new ProcessorPluginResolver();
        $connectionTransfer = $this->buildConnection(DummyNonPunchoutProcessor::class);

        // Expect
        $this->expectException(WrongProcessorException::class);
        $this->expectExceptionMessageMatches('/not of a valid interface/');

        // Act
        $resolver->resolveProcessorPlugin($connectionTransfer, PunchoutProcessorPluginInterface::class);
    }

    public function testResolveProcessorPluginThrowsWhenBaseProcessorDoesNotImplementCxmlInterface(): void
    {
        // Arrange
        $resolver = new ProcessorPluginResolver();
        $connectionTransfer = $this->buildConnection(DummyPunchoutProcessorPlugin::class);

        // Expect
        $this->expectException(WrongProcessorException::class);
        $this->expectExceptionMessageMatches('/not of a valid interface/');

        // Act
        $resolver->resolveProcessorPlugin($connectionTransfer, PunchoutCxmlProcessorPluginInterface::class);
    }

    protected function buildConnection(string $pluginClass): PunchoutConnectionTransfer
    {
        return (new PunchoutConnectionTransfer())
            ->setProcessorPluginClass($pluginClass)
            ->setName('test-connection')
            ->setIdPunchoutConnection(1);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class DummyPunchoutProcessorPlugin implements PunchoutProcessorPluginInterface
{
    public function authenticate(PunchoutSetupRequestTransfer $setupRequestTransfer): ?PunchoutConnectionTransfer
    {
        return null;
    }

    public function resolveCustomer(PunchoutSetupRequestTransfer $setupRequestTransfer): ?CustomerTransfer
    {
        return null;
    }

    public function resolveQuote(PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer
    {
        return new QuoteTransfer();
    }

    public function expandQuote(QuoteTransfer $quoteTransfer, PunchoutSetupRequestTransfer $setupRequestTransfer): QuoteTransfer
    {
        return $quoteTransfer;
    }

    public function resolveSession(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupRequestTransfer $setupRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): ?PunchoutSessionTransfer {
        return null;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class DummyPunchoutCxmlProcessorPlugin extends DummyPunchoutProcessorPlugin implements PunchoutCxmlProcessorPluginInterface
{
    public function parseCxmlRequest(
        PunchoutCxmlSetupRequestTransfer $cxmlSetupRequestTransfer,
        CXml $cxml,
    ): PunchoutCxmlSetupRequestTransfer {
        return $cxmlSetupRequestTransfer;
    }

    public function expandResponse(
        PunchoutSessionTransfer $punchoutSessionTransfer,
        PunchoutSetupResponseTransfer $responseTransfer,
        PunchoutCxmlSetupRequestTransfer $punchoutCxmlSetupRequestTransfer,
    ): PunchoutSetupResponseTransfer {
        return $responseTransfer;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class DummyNonPunchoutProcessor
{
}
