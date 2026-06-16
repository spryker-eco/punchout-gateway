<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form\Validator;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;

class MappingCollectionValidator implements MappingCollectionValidatorInterface
{
    public function validate(FormEvent $event, string $collectionName, string $keyField, string $message): void
    {
        $collection = $event->getForm()->get($collectionName);
        $seen = [];

        foreach ($collection as $row) {
            $value = $row->get($keyField)->getData();

            if (!$value) {
                continue;
            }

            if (in_array($value, $seen, true)) {
                $row->get($keyField)->addError(new FormError(sprintf($message, $value)));

                continue;
            }

            $seen[] = $value;
        }
    }
}
