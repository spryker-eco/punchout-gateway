<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
class PunchoutGatewayCommunicationTester extends Actor
{
    use _generated\PunchoutGatewayCommunicationTesterActions;
}
