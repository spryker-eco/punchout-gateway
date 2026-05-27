<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Table;

class PunchoutCredentialViewTable extends PunchoutCredentialTable
{
    protected function buildCredentialActionButtons(array $row): string
    {
        return '';
    }
}
