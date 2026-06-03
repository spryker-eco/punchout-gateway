<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use SprykerEco\Shared\PunchoutGateway\Logger\NullPunchoutLogger;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Validator\CxmlRequestValidator;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Validator\CxmlRequestValidatorInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Business
 * @group CxmlRequestValidatorTest
 */
class CxmlRequestValidatorTest extends Unit
{
    protected CxmlRequestValidatorInterface $validator;

    public function _before(): void
    {
        $this->validator = new CxmlRequestValidator(
            new NullPunchoutLogger(),
        );
    }

    public function testValidateReturnsTrueWhenOperationIsNull(): void
    {
        $transfer = new PunchoutCxmlSetupRequestTransfer();

        $this->assertTrue($this->validator->validate($transfer));
    }

    public function testValidateReturnsTrueForCreateOperation(): void
    {
        $transfer = (new PunchoutCxmlSetupRequestTransfer())->setOperation('create');

        $this->assertTrue($this->validator->validate($transfer));
    }

    public function testValidateReturnsTrueForEditOperation(): void
    {
        $transfer = (new PunchoutCxmlSetupRequestTransfer())->setOperation('edit');

        $this->assertTrue($this->validator->validate($transfer));
    }

    public function testValidateReturnsTrueForInspectOperation(): void
    {
        $transfer = (new PunchoutCxmlSetupRequestTransfer())->setOperation('inspect');

        $this->assertTrue($this->validator->validate($transfer));
    }

    public function testValidateReturnsFalseForUnknownOperation(): void
    {
        $transfer = (new PunchoutCxmlSetupRequestTransfer())->setOperation('unknown');

        $this->assertFalse($this->validator->validate($transfer));
    }
}
