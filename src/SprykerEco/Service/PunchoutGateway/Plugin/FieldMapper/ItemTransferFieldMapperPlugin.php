<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MappingSourceTransfer;
use Spryker\Service\Kernel\AbstractPlugin;
use SprykerEco\Service\PunchoutGateway\Dependency\Plugin\PunchoutFieldMapperPluginInterface;

/**
 * @method \SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig getConfig()
 */
class ItemTransferFieldMapperPlugin extends AbstractPlugin implements PunchoutFieldMapperPluginInterface
{
    use TransferPathTraversalTrait;

    /**
     * {@inheritDoc}
     */
    public function getPossibleValues(): array
    {
        return $this->collectPossibleValues('item', ItemTransfer::class);
    }

    public function getValue(MappingSourceTransfer $mappingSourceTransfer, string $fieldName): mixed
    {
        return $this->traversePath($mappingSourceTransfer->getItem(), $fieldName);
    }
}
