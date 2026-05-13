<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway;

use Codeception\Actor;

/**
 * @method void wantTo($text)
 * @method void execute($callable)
 * @SuppressWarnings(PHPMD)
 */
class PunchoutGatewayPersistenceTester extends Actor
{
    use _generated\PunchoutGatewayPersistenceTesterActions;
}
