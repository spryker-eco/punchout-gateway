<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form\DataProvider;

use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCredentialFormType;

class PunchoutCredentialFormDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public function getData(?PunchoutCredentialTransfer $punchoutCredentialTransfer = null): array
    {
        if ($punchoutCredentialTransfer === null) {
            return [];
        }

        return $punchoutCredentialTransfer->toArray(true, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(
        bool $isEdit = false,
        ?int $idCustomer = null,
        int $idPunchoutConnection = 0,
        ?int $idPunchoutCredential = null,
    ): array {
        return [
            PunchoutCredentialFormType::OPTION_IS_EDIT => $isEdit,
            PunchoutCredentialFormType::OPTION_PRESELECTED_ID_CUSTOMER => $idCustomer,
            PunchoutCredentialFormType::OPTION_ID_PUNCHOUT_CONNECTION => $idPunchoutConnection,
            PunchoutCredentialFormType::OPTION_PRESELECTED_ID_PUNCHOUT_CREDENTIAL => $idPunchoutCredential,
        ];
    }
}
