<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Service\PunchoutGateway;

use Codeception\Actor;

/**
 * @method void wantTo($text)
 * @method void execute($callable)
 * @SuppressWarnings(PHPMD)
 */
class PunchoutGatewayServiceTester extends Actor
{
    use _generated\PunchoutGatewayServiceTesterActions;
}
