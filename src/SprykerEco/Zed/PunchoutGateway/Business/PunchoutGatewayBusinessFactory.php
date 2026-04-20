<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Business;

use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\NullPunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Authenticator\PunchoutCxmlAuthenticator;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Authenticator\PunchoutCxmlAuthenticatorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Customer\CxmlCustomerResolver;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Customer\CxmlCustomerResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Parser\DefaultCxmlContentParser;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Parser\DefaultCxmlContentParserInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Processor\PunchoutCxmlSetupRequestProcessor;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Processor\PunchoutCxmlSetupRequestProcessorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote\CxmlPunchoutQuoteExpander;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote\CxmlPunchoutQuoteExpanderInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote\CxmlPunchoutQuoteFinder;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Quote\CxmlPunchoutQuoteFinderInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Response\CxmlPunchoutResponseExpander;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Response\CxmlPunchoutResponseExpanderInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\CxmlPunchoutSessionResolver;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\CxmlPunchoutSessionResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\PunchoutSessionStarter;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\PunchoutSessionStarterInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Model\ProcessorPluginResolver;
use SprykerEco\Zed\PunchoutGateway\Business\Model\ProcessorPluginResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator\PunchoutOciAuthenticator;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator\PunchoutOciAuthenticatorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Customer\OciCustomerResolver;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Customer\OciCustomerResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Processor\PunchoutOciLoginProcessor;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Processor\PunchoutOciLoginProcessorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Quote\OciPunchoutQuoteFinder;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Quote\OciPunchoutQuoteFinderInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Session\OciPunchoutSessionResolver;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Session\OciPunchoutSessionResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\PunchoutQuoteExpander;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\PunchoutQuoteExpanderInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\QuoteCreator;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\QuoteCreatorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Session\SessionCreator;
use SprykerEco\Zed\PunchoutGateway\Business\Session\SessionCreatorInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayDependencyProvider;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface getEntityManager()
 */
class PunchoutGatewayBusinessFactory extends AbstractBusinessFactory
{
    public function createSessionCreator(): SessionCreatorInterface
    {
        return new SessionCreator(
            $this->createPunchoutLogger(),
            $this->getEntityManager(),
        );
    }

    public function createQuoteCreator(): QuoteCreatorInterface
    {
        return new QuoteCreator(
            $this->getQuoteFacade(),
            $this->getStoreFacade(),
            $this->createPunchoutLogger(),
        );
    }

    public function createPunchoutCxmlSetupRequestProcessor(): PunchoutCxmlSetupRequestProcessorInterface
    {
        return new PunchoutCxmlSetupRequestProcessor(
            $this->getPunchoutGatewayService(),
            $this->createQuoteCreator(),
            $this->createSessionCreator(),
            $this->createProcessorPluginResolver(),
            $this->createPunchoutLogger(),
            $this->getRepository(),
        );
    }

    public function createPunchoutOciLoginProcessor(): PunchoutOciLoginProcessorInterface
    {
        return new PunchoutOciLoginProcessor(
            $this->createQuoteCreator(),
            $this->createSessionCreator(),
            $this->createProcessorPluginResolver(),
            $this->createPunchoutLogger(),
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    public function createPunchoutCxmlSessionStarter(): PunchoutSessionStarterInterface
    {
        return new PunchoutSessionStarter(
            $this->getCustomerFacade(),
            $this->getQuoteFacade(),
            $this->createPunchoutLogger(),
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    public function createPunchoutOciAuthenticator(): PunchoutOciAuthenticatorInterface
    {
        return new PunchoutOciAuthenticator(
            $this->getRepository(),
            $this->createPunchoutLogger(),
        );
    }

    public function createPunchoutQuoteExpander(): PunchoutQuoteExpanderInterface
    {
        return new PunchoutQuoteExpander(
            $this->getRepository(),
            $this->getPunchoutSessionInQuoteExpanderPlugins(),
        );
    }

    public function createCxmlCustomerResolver(): CxmlCustomerResolverInterface
    {
        return new CxmlCustomerResolver(
            $this->getCustomerFacade(),
            $this->createPunchoutLogger(),
        );
    }

    public function createOciCustomerResolver(): OciCustomerResolverInterface
    {
        return new OciCustomerResolver(
            $this->getCustomerFacade(),
            $this->createPunchoutLogger(),
        );
    }

    public function createCxmlPunchoutQuoteExpander(): CxmlPunchoutQuoteExpanderInterface
    {
        return new CxmlPunchoutQuoteExpander();
    }

    public function createCxmlPunchoutSessionResolver(): CxmlPunchoutSessionResolverInterface
    {
        return new CxmlPunchoutSessionResolver(
            $this->getConfig(),
        );
    }

    public function createCxmlPunchoutResponseExpander(): CxmlPunchoutResponseExpanderInterface
    {
        return new CxmlPunchoutResponseExpander();
    }

    public function createCxmlPunchoutQuoteFinder(): CxmlPunchoutQuoteFinderInterface
    {
        return new CxmlPunchoutQuoteFinder(
            $this->getQuoteFacade(),
            $this->getRepository(),
            $this->createPunchoutLogger(),
        );
    }

    public function createOciPunchoutQuoteFinder(): OciPunchoutQuoteFinderInterface
    {
        return new OciPunchoutQuoteFinder();
    }

    public function createOciPunchoutSessionResolver(): OciPunchoutSessionResolverInterface
    {
        return new OciPunchoutSessionResolver(
            $this->createPunchoutLogger(),
        );
    }

    public function createPunchoutCxmlAuthenticator(): PunchoutCxmlAuthenticatorInterface
    {
        return new PunchoutCxmlAuthenticator(
            $this->createPunchoutLogger(),
        );
    }

    public function createPunchoutLogger(): PunchoutLoggerInterface
    {
        if (!$this->getConfig()->isLoggingEnabled()) {
            return new NullPunchoutLogger();
        }

        return new PunchoutLogger();
    }

    public function createDefaultCxmlContentParser(): DefaultCxmlContentParserInterface
    {
        return new DefaultCxmlContentParser();
    }

    public function createProcessorPluginResolver(): ProcessorPluginResolverInterface
    {
        return new ProcessorPluginResolver();
    }

    /**
     * @return array<\SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutSessionInQuoteExpanderPluginInterface>
     */
    public function getPunchoutSessionInQuoteExpanderPlugins(): array
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::PLUGINS_PUNCHOUT_SESSION_IN_QUOTE_EXPANDER);
    }

    public function getQuoteFacade(): QuoteFacadeInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::FACADE_QUOTE);
    }

    public function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::FACADE_STORE);
    }

    public function getCustomerFacade(): CustomerFacadeInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::FACADE_CUSTOMER);
    }

    public function getPunchoutGatewayService(): PunchoutGatewayServiceInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::SERVICE_PUNCHOUT_GATEWAY);
    }
}
