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
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayBusinessFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @SuppressWarnings(PHPMD)
 */
class PunchoutGatewayBusinessTester extends Actor
{
    use _generated\PunchoutGatewayBusinessTesterActions;
}
