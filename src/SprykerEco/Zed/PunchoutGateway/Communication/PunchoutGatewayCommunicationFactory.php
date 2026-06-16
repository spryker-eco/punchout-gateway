<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication;

use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\CustomerChoiceLoader;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\DataProvider\PunchoutConnectionFormDataProvider;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\DataProvider\PunchoutCredentialFormDataProvider;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutConnectionFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCredentialFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\Validator\MappingCollectionValidator;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\Validator\MappingCollectionValidatorInterface;
use SprykerEco\Zed\PunchoutGateway\Communication\SourceFieldSuggestion\SourceFieldSuggestionFilter;
use SprykerEco\Zed\PunchoutGateway\Communication\SourceFieldSuggestion\SourceFieldSuggestionFilterInterface;
use SprykerEco\Zed\PunchoutGateway\Communication\Table\PunchoutConnectionTable;
use SprykerEco\Zed\PunchoutGateway\Communication\Table\PunchoutCredentialTable;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayDependencyProvider;
use Symfony\Component\Form\FormInterface;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface getEntityManager()
 */
class PunchoutGatewayCommunicationFactory extends AbstractCommunicationFactory
{
    public function createMappingCollectionValidator(): MappingCollectionValidatorInterface
    {
        return new MappingCollectionValidator();
    }

    public function createSourceFieldSuggestionFilter(): SourceFieldSuggestionFilterInterface
    {
        return new SourceFieldSuggestionFilter(
            $this->getConfig(),
        );
    }

    public function createPunchoutConnectionTable(): PunchoutConnectionTable
    {
        return new PunchoutConnectionTable(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    public function createPunchoutCredentialTable(int $idPunchoutConnection): PunchoutCredentialTable
    {
        return new PunchoutCredentialTable(
            $this->getRepository(),
            $idPunchoutConnection,
            sprintf('%s?%s=%d&', PunchoutGatewayConfig::URL_CREDENTIAL_TABLE, PunchoutGatewayConfig::PARAM_ID_CONNECTION, $idPunchoutConnection),
        );
    }

    public function createPunchoutConnectionFormDataProvider(): PunchoutConnectionFormDataProvider
    {
        return new PunchoutConnectionFormDataProvider(
            $this->getStoreFacade(),
            $this->getProcessorPlugins(),
            $this->getPunchoutGatewayService(),
        );
    }

    public function getPunchoutGatewayService(): PunchoutGatewayServiceInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::SERVICE_PUNCHOUT_GATEWAY);
    }

    public function createPunchoutCredentialFormDataProvider(): PunchoutCredentialFormDataProvider
    {
        return new PunchoutCredentialFormDataProvider();
    }

    /**
     * @param array<string, mixed>|null $data
     * @param array<string, mixed> $options
     */
    public function createPunchoutConnectionForm(?array $data, array $options = []): FormInterface
    {
        return $this->getFormFactory()->create(PunchoutConnectionFormType::class, $data, $options);
    }

    /**
     * @param array<string, mixed>|null $data
     * @param array<string, mixed> $options
     */
    public function createPunchoutCredentialForm(?array $data, array $options = []): FormInterface
    {
        return $this->getFormFactory()->create(PunchoutCredentialFormType::class, $data, $options);
    }

    public function createCustomerChoiceLoader(?int $preselectedIdCustomer = null): CustomerChoiceLoader
    {
        return new CustomerChoiceLoader($this->getCustomerFacade(), $preselectedIdCustomer);
    }

    public function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::FACADE_STORE);
    }

    public function getCustomerFacade(): CustomerFacadeInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::FACADE_CUSTOMER);
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    /**
     * @return array<string, string>
     */
    public function getProcessorPlugins(): array
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::PLUGINS_PROCESSORS);
    }
}
