<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway;

use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Quote\QuoteClientInterface;
use Spryker\Client\Session\SessionClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use SprykerEco\Client\PunchoutGateway\PunchoutGatewayClientInterface;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\NullPunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Yves\PunchoutGateway\Expander\PunchoutSecurityHeaderExpander;
use SprykerEco\Yves\PunchoutGateway\Expander\PunchoutSecurityHeaderExpanderInterface;
use SprykerEco\Yves\PunchoutGateway\FormBuilder\OciFormFieldBuilder;
use SprykerEco\Yves\PunchoutGateway\FormBuilder\OciFormFieldBuilderInterface;
use SprykerEco\Yves\PunchoutGateway\FormBuilder\PunchoutFormDataBuilder;
use SprykerEco\Yves\PunchoutGateway\FormBuilder\PunchoutFormDataBuilderInterface;
use SprykerEco\Yves\PunchoutGateway\Model\LoginModel;
use SprykerEco\Yves\PunchoutGateway\Model\LoginModelInterface;
use SprykerEco\Yves\PunchoutGateway\Model\PunchoutSecurityHeaderSessionWriter;
use SprykerEco\Yves\PunchoutGateway\Model\PunchoutSecurityHeaderSessionWriterInterface;
use SprykerEco\Yves\PunchoutGateway\ResponseBuilder\CxmlResponseBuilder;
use SprykerEco\Yves\PunchoutGateway\ResponseBuilder\CxmlResponseBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @method \SprykerEco\Yves\PunchoutGateway\PunchoutGatewayConfig getConfig()
 */
class PunchoutGatewayFactory extends AbstractFactory
{
    public function getPunchoutGatewayClient(): PunchoutGatewayClientInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::CLIENT_PUNCHOUT_GATEWAY);
    }

    public function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::CLIENT_CUSTOMER);
    }

    public function getSessionClient(): SessionClientInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::CLIENT_SESSION);
    }

    public function getSecurityTokenStorage(): TokenStorageInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::SERVICE_SECURITY_TOKEN_STORAGE);
    }

    public function createCxmlResponseBuilder(): CxmlResponseBuilderInterface
    {
        return new CxmlResponseBuilder(
            $this->getPunchoutGatewayService(),
        );
    }

    public function getPunchoutGatewayService(): PunchoutGatewayServiceInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::SERVICE_PUNCHOUT_GATEWAY);
    }

    public function createPunchoutLogger(): PunchoutLoggerInterface
    {
        if (!$this->getConfig()->isLoggingEnabled()) {
            return new NullPunchoutLogger();
        }

        return new PunchoutLogger();
    }

    public function createLoginModel(): LoginModelInterface
    {
        return new LoginModel(
            $this->getSecurityTokenStorage(),
            $this->getSessionClient(),
            $this->getCustomerClient(),
            $this->getQuoteClient(),
            $this->createPunchoutLogger(),
        );
    }

    public function createPunchoutSecurityHeaderExpander(): PunchoutSecurityHeaderExpanderInterface
    {
        return new PunchoutSecurityHeaderExpander($this->getSessionClient());
    }

    public function createPunchoutSecurityHeaderSessionWriter(): PunchoutSecurityHeaderSessionWriterInterface
    {
        return new PunchoutSecurityHeaderSessionWriter(
            $this->getSessionClient(),
            $this->getPunchoutSecurityHeaderExpanderPlugins(),
        );
    }

    /**
     * @return array<\SprykerEco\Yves\PunchoutGateway\Dependency\Plugin\PunchoutSecurityHeaderExpanderPluginInterface>
     */
    public function getPunchoutSecurityHeaderExpanderPlugins(): array
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::PLUGINS_PUNCHOUT_SECURITY_HEADER_EXPANDER);
    }

    public function getQuoteClient(): QuoteClientInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::CLIENT_QUOTE);
    }

    public function createPunchoutFormDataBuilder(): PunchoutFormDataBuilderInterface
    {
        return new PunchoutFormDataBuilder(
            $this->getPunchoutFormHandlerPlugins(),
            $this->createPunchoutLogger(),
        );
    }

    public function createOciFormFieldBuilder(): OciFormFieldBuilderInterface
    {
        return new OciFormFieldBuilder();
    }

    /**
     * @return array<\SprykerEco\Yves\PunchoutGateway\Plugin\Form\PunchoutFormHandlerPluginInterface>
     */
    public function getPunchoutFormHandlerPlugins(): array
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::PLUGINS_PUNCHOUT_FORM_HANDLER);
    }
}
