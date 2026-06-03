<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEcoTest\Service\PunchoutGateway\Plugin\FieldMapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper\TransferPathTraversalTrait;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEcoTest\Service\PunchoutGateway\PunchoutGatewayServiceTester;

/**
 * @group SprykerEcoTest
 * @group Service
 * @group PunchoutGateway
 * @group Plugin
 * @group FieldMapper
 * @group TransferPathTraversalTraitTest
 */
class TransferPathTraversalTraitTest extends Unit
{
    protected PunchoutGatewayServiceTester $tester;

    public function testTraversePathReturnsArrayValueWhenInputIsArrayAndKeyExists(): void
    {
        // Arrange
        $subject = $this->createTraitSubject();

        // Act
        $result = $subject->traversePath(['color' => 'red'], 'color');

        // Assert
        $this->assertSame('red', $result);
    }

    public function testTraversePathReturnsNullWhenInputIsArrayAndKeyMissing(): void
    {
        // Arrange
        $subject = $this->createTraitSubject();

        // Act
        $result = $subject->traversePath(['color' => 'red'], 'size');

        // Assert
        $this->assertNull($result);
    }

    public function testTraversePathReturnsArrayValueWhenGetterReturnsArrayAndKeyExists(): void
    {
        // Arrange
        $subject = $this->createTraitSubject();
        $itemTransfer = (new ItemTransfer())->setConcreteAttributes(['color' => 'red']);

        // Act
        $result = $subject->traversePath($itemTransfer, 'concreteAttributes.color');

        // Assert
        $this->assertSame('red', $result);
    }

    public function testTraversePathReturnsNullWhenGetterReturnsArrayAndKeyMissing(): void
    {
        // Arrange
        $subject = $this->createTraitSubject();
        $itemTransfer = (new ItemTransfer())->setConcreteAttributes(['color' => 'red']);

        // Act
        $result = $subject->traversePath($itemTransfer, 'concreteAttributes.size');

        // Assert
        $this->assertNull($result);
    }

    public function testTraversePathReturnsArrayValueAndIgnoresRemainingSegmentsAfterArrayHit(): void
    {
        // Arrange
        $subject = $this->createTraitSubject();
        $itemTransfer = (new ItemTransfer())->setConcreteAttributes(['color' => 'red']);

        // Act — extra segment after array hit is ignored due to early return
        $result = $subject->traversePath($itemTransfer, 'concreteAttributes.color.extra');

        // Assert
        $this->assertSame('red', $result);
    }

    protected function createTraitSubject(): object
    {
        return new class (new PunchoutGatewayConfig()) {
            use TransferPathTraversalTrait {
                traversePath as public;
            }

            public function __construct(protected PunchoutGatewayConfig $config)
            {
            }

            public function getConfig(): PunchoutGatewayConfig
            {
                return $this->config;
            }
        };
    }
}
