<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Mapper\Resolver;

use Generated\Shared\Transfer\MappingSourceTransfer;

interface FieldValueResolverInterface
{
    public function resolve(?string $expression, MappingSourceTransfer $mappingSourceTransfer): mixed;
}
