<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Form\Validator;

use ArrayIterator;
use Codeception\Test\Unit;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\Validator\MappingCollectionValidator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group Form
 * @group Validator
 * @group MappingCollectionValidatorTest
 */
class MappingCollectionValidatorTest extends Unit
{
    protected const string COLLECTION_NAME = 'mappingFields';

    protected const string KEY_FIELD = 'field';

    protected const string MESSAGE = 'Field "%s" is already mapped in another row.';

    protected MappingCollectionValidator $validator;

    protected function _before(): void
    {
        $this->validator = new MappingCollectionValidator();
    }

    public function testValidateWithDistinctKeyValuesAddsNoErrors(): void
    {
        // Arrange
        $row1 = $this->buildRowMock('sku', expectsError: false);
        $row2 = $this->buildRowMock('name', expectsError: false);
        $event = $this->buildEvent([$row1, $row2]);

        // Act
        $this->validator->validate($event, static::COLLECTION_NAME, static::KEY_FIELD, static::MESSAGE);
    }

    public function testValidateWithDuplicateKeyValueAddsErrorToSecondRow(): void
    {
        // Arrange
        $row1 = $this->buildRowMock('sku', expectsError: false);
        $row2 = $this->buildRowMock('sku', expectsError: true);
        $event = $this->buildEvent([$row1, $row2]);

        // Act
        $this->validator->validate($event, static::COLLECTION_NAME, static::KEY_FIELD, static::MESSAGE);
    }

    public function testValidateWithEmptyKeyValueSkipsRow(): void
    {
        // Arrange
        $row1 = $this->buildRowMock('', expectsError: false);
        $row2 = $this->buildRowMock('', expectsError: false);
        $event = $this->buildEvent([$row1, $row2]);

        // Act
        $this->validator->validate($event, static::COLLECTION_NAME, static::KEY_FIELD, static::MESSAGE);
    }

    public function testValidateWithThirdRowDuplicatingFirstAddsErrorToThirdRowOnly(): void
    {
        // Arrange
        $row1 = $this->buildRowMock('sku', expectsError: false);
        $row2 = $this->buildRowMock('name', expectsError: false);
        $row3 = $this->buildRowMock('sku', expectsError: true);
        $event = $this->buildEvent([$row1, $row2, $row3]);

        // Act
        $this->validator->validate($event, static::COLLECTION_NAME, static::KEY_FIELD, static::MESSAGE);
    }

    protected function buildRowMock(string $keyValue, bool $expectsError): FormInterface
    {
        $keyFieldMock = $this->createMock(FormInterface::class);
        $keyFieldMock->method('getData')->willReturn($keyValue);

        if ($expectsError) {
            $keyFieldMock->expects($this->once())
                ->method('addError')
                ->with($this->isInstanceOf(FormError::class));
        } else {
            $keyFieldMock->expects($this->never())->method('addError');
        }

        $rowMock = $this->createMock(FormInterface::class);
        $rowMock->method('get')->with(static::KEY_FIELD)->willReturn($keyFieldMock);

        return $rowMock;
    }

    /**
     * @param array<\Symfony\Component\Form\FormInterface> $rows
     */
    protected function buildEvent(array $rows): FormEvent
    {
        $collectionMock = $this->createMock(Form::class);
        $collectionMock->method('getIterator')->willReturn(new ArrayIterator($rows));

        $formMock = $this->createMock(FormInterface::class);
        $formMock->method('get')->with(static::COLLECTION_NAME)->willReturn($collectionMock);

        return new FormEvent($formMock, null);
    }
}
