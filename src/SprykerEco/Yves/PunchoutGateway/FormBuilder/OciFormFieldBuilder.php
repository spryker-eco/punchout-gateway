<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\FormBuilder;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutFormDataTransfer;
use Generated\Shared\Transfer\PunchoutOciLoginRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class OciFormFieldBuilder implements OciFormFieldBuilderInterface
{
    protected const string FIELD_TARGET = '~TARGET';

    protected const string FIELD_DESCRIPTION = 'NEW_ITEM-DESCRIPTION[%d]';

    protected const string FIELD_QUANTITY = 'NEW_ITEM-QUANTITY[%d]';

    protected const string FIELD_UNIT = 'NEW_ITEM-UNIT[%d]';

    protected const string FIELD_PRICE = 'NEW_ITEM-PRICE[%d]';

    protected const string FIELD_CURRENCY = 'NEW_ITEM-CURRENCY[%d]';

    protected const string FIELD_VENDORMAT = 'NEW_ITEM-VENDORMAT[%d]';

    protected const int PRICE_DIVISOR = 100;

    /**
     * @var array<string>
     */
    protected const array SAP_ECHO_FIELDS = ['~OkCode', '~TARGET', '~CALLER'];

    public function build(QuoteTransfer $quoteTransfer): ?PunchoutFormDataTransfer
    {
        $punchoutSession = $quoteTransfer->getPunchoutSession();

        if (!$punchoutSession) {
            return null;
        }

        $actionUrl = $punchoutSession->getBrowserFormPostUrl();

        if (!$actionUrl) {
            return null;
        }

        $ociLoginRequest = $punchoutSession->getPunchoutData()?->getOciLoginRequest();

        if (!$ociLoginRequest) {
            return null;
        }

        $formData = (new PunchoutFormDataTransfer())->setActionUrl($actionUrl);

        $this->setFormTarget($formData, $ociLoginRequest);
        $this->addItemFields($formData, $quoteTransfer);
        $this->addSapEchoFields($formData, $ociLoginRequest);

        return $formData;
    }

    protected function addItemFields(PunchoutFormDataTransfer $formDataTransfer, QuoteTransfer $quoteTransfer): void
    {
        $currencyCode = (string)$quoteTransfer->getCurrency()?->getCode();
        $index = 1;

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $this->addSingleItemFields($formDataTransfer, $itemTransfer, $index, $currencyCode, $quoteTransfer->getCurrency()->getFractionDigits());
            $index++;
        }
    }

    protected function addSingleItemFields(
        PunchoutFormDataTransfer $formData,
        ItemTransfer $itemTransfer,
        int $index,
        string $currencyCode,
        int $fractionDigits
    ): void {
        $formData->addField(sprintf(static::FIELD_DESCRIPTION, $index), (string)$itemTransfer->getName());
        $formData->addField(sprintf(static::FIELD_QUANTITY, $index), (string)$itemTransfer->getQuantity());
        $formData->addField(sprintf(static::FIELD_UNIT, $index), PunchoutGatewayConfig::DEFAULT_UNIT_OF_MEASURE);
        $formData->addField(sprintf(static::FIELD_PRICE, $index), $this->formatPrice((int)$itemTransfer->getUnitPrice(), $fractionDigits));
        $formData->addField(sprintf(static::FIELD_CURRENCY, $index), $currencyCode);
        $formData->addField(sprintf(static::FIELD_VENDORMAT, $index), (string)$itemTransfer->getSku());
    }

    protected function formatPrice(int $centAmount, int $fractionDigits): string
    {
        return number_format($centAmount / (10 ** $fractionDigits), 3, '.', '');
    }

    protected function setFormTarget(
        PunchoutFormDataTransfer $formData,
        PunchoutOciLoginRequestTransfer $ociLoginRequest,
    ): void {
        $loginFormData = $ociLoginRequest->getFormData();

        if (!isset($loginFormData[static::FIELD_TARGET])) {
            return;
        }

        $formData->setFormTarget($loginFormData[static::FIELD_TARGET]);
    }

    protected function addSapEchoFields(
        PunchoutFormDataTransfer $formData,
        PunchoutOciLoginRequestTransfer $ociLoginRequest,
    ): void {
        $loginFormData = $ociLoginRequest->getFormData();

        foreach (static::SAP_ECHO_FIELDS as $fieldName) {
            if (!isset($loginFormData[$fieldName])) {
                continue;
            }

            $formData->addField($fieldName, $loginFormData[$fieldName]);
        }
    }
}
