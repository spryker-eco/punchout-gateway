<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Service\PunchoutGateway\Mapper;

use CXml\Builder;
use CXml\Builder\PunchOutOrderMessageBuilder;
use CXml\Model\Classification;
use CXml\Model\Country;
use CXml\Model\Credential;
use CXml\Model\ItemId;
use CXml\Model\Message\PunchOutOrderMessage;
use CXml\Model\PostalAddress;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PunchoutCxmlSetupRequestTransfer;
use Generated\Shared\Transfer\PunchoutSessionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoderInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class CxmlPunchoutOrderMessageMapper implements CxmlPunchoutOrderMessageMapperInterface
{
    protected const string CLASSIFICATION_UNIT_OF_MEASURE = 'UNSPSC';

    public function __construct(
        protected CxmlEncoderInterface $cxmlEncoder,
        protected PunchoutLoggerInterface $punchoutLogger
    ) {
    }

    public function mapQuoteToCxml(QuoteTransfer $quoteTransfer): string
    {
        $punchoutSession = $quoteTransfer->getPunchoutSession();

        if ($punchoutSession === null) {
            return '';
        }

        $cxmlSetupRequest = $this->resolveCxmlSetupRequest($punchoutSession);

        if ($cxmlSetupRequest === null) {
            return '';
        }

        $punchoutOrderMessage = $this->buildPunchoutOrderMessage($quoteTransfer, $punchoutSession);

        $cxml = Builder::create(PunchoutGatewayConfig::DEFAULT_CXML_SENDER_USER_AGENT, PunchoutGatewayConfig::DEFAULT_CXML_LANGUAGE)
            ->from(new Credential(PunchoutGatewayConfig::DEFAULT_CXML_CREDENTIAL_DOMAIN, (string)$cxmlSetupRequest->getToIdentity()))
            ->to(new Credential(PunchoutGatewayConfig::DEFAULT_CXML_CREDENTIAL_DOMAIN, (string)$cxmlSetupRequest->getFromIdentity()))
            ->sender(
                (new Credential(PunchoutGatewayConfig::DEFAULT_CXML_CREDENTIAL_DOMAIN, (string)$cxmlSetupRequest->getToIdentity()))
                    ->setSharedSecret($cxmlSetupRequest->getSenderSharedSecret()),
            )
            ->payload($punchoutOrderMessage)
            ->build();

        return $this->cxmlEncoder->encodeCxml($cxml);
    }

    protected function resolveCxmlSetupRequest(PunchoutSessionTransfer $punchoutSession): ?PunchoutCxmlSetupRequestTransfer
    {
        $punchoutData = $punchoutSession->getPunchoutData();

        if ($punchoutData === null || $punchoutData->getCxmlSetupRequest() === null) {
            $this->punchoutLogger->logGenericErrorMessage('PunchoutSession must carry punchoutData.cxmlSetupRequest to build PunchOutOrderMessage.');

            return null;
        }

        return $punchoutData->getCxmlSetupRequest();
    }

    protected function buildPunchoutOrderMessage(
        QuoteTransfer $quoteTransfer,
        PunchoutSessionTransfer $punchoutSession,
    ): PunchOutOrderMessage {
        $currencyCode = (string)$quoteTransfer->getCurrency()?->getCode();
        $buyerCookie = (string)$punchoutSession->getBuyerCookie();
        $operation = $punchoutSession->getOperation();

        $builder = PunchOutOrderMessageBuilder::create(
            PunchoutGatewayConfig::DEFAULT_CXML_LANGUAGE,
            $buyerCookie,
            $currencyCode,
            $operation,
        );

        $this->addItems($builder, $quoteTransfer);
        $this->addShipTo($builder, $quoteTransfer);
        $this->addShipping($builder, $quoteTransfer);
        $this->addTax($builder, $quoteTransfer);

        return $builder->build();
    }

    protected function addItems(PunchOutOrderMessageBuilder $builder, QuoteTransfer $quoteTransfer): void
    {
        foreach ($quoteTransfer->getItems() as $item) {
            $this->addItem($builder, $item);
        }
    }

    protected function addItem(PunchOutOrderMessageBuilder $builder, ItemTransfer $item): void
    {
        $itemId = new ItemId(
            (string)$item->getSku(),
            null,
            $item->getGroupKey(),
        );

        $builder->addPunchoutOrderMessageItem(
            $itemId,
            (int)$item->getQuantity(),
            (string)$item->getName(),
            PunchoutGatewayConfig::DEFAULT_UNIT_OF_MEASURE,
            (int)$item->getUnitPrice(),
            [new Classification(static::CLASSIFICATION_UNIT_OF_MEASURE, '')],
        );
    }

    protected function addShipTo(PunchOutOrderMessageBuilder $builder, QuoteTransfer $quoteTransfer): void
    {
        $address = $quoteTransfer->getShippingAddress();

        if ($address === null) {
            return;
        }

        $streetLines = array_values(array_filter([
            $address->getAddress1(),
            $address->getAddress2(),
            $address->getAddress3(),
        ]));

        $postalAddress = new PostalAddress(
            [],
            $streetLines,
            (string)$address->getCity(),
            new Country((string)$address->getIso2Code()),
            null,
            $address->getRegion() ?? $address->getState(),
            $address->getZipCode(),
        );

        $addressName = trim(sprintf('%s %s', $address->getFirstName(), $address->getLastName()));

        $builder->shipTo($addressName ?: 'Ship To', $postalAddress);
    }

    protected function addShipping(PunchOutOrderMessageBuilder $builder, QuoteTransfer $quoteTransfer): void
    {
        $totals = $quoteTransfer->getTotals();

        if ($totals === null || $totals->getExpenseTotal() === null) {
            return;
        }

        $builder->shipping((int)$totals->getExpenseTotal(), PunchoutGatewayConfig::DEFAULT_CXML_SHIPPING_DESCRIPTION);
    }

    protected function addTax(PunchOutOrderMessageBuilder $builder, QuoteTransfer $quoteTransfer): void
    {
        $totals = $quoteTransfer->getTotals();

        if ($totals === null || $totals->getTaxTotal() === null || $totals->getTaxTotal()->getAmount() === null) {
            return;
        }

        $builder->tax((int)$totals->getTaxTotal()->getAmount(), PunchoutGatewayConfig::DEFAULT_CXML_TAX_DESCRIPTION);
    }
}
