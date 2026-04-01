<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\PunchoutGateway\Business;

use CXml\Serializer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
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
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\CxmlPunchoutSessionExpander;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\CxmlPunchoutSessionExpanderInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\PunchoutSessionStarter;
use SprykerEco\Zed\PunchoutGateway\Business\Cxml\Session\PunchoutSessionStarterInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator\PunchoutOciAuthenticator;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Authenticator\PunchoutOciAuthenticatorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Customer\OciCustomerResolver;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Customer\OciCustomerResolverInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Processor\PunchoutOciLoginProcessor;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Processor\PunchoutOciLoginProcessorInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Session\OciPunchoutSessionExpander;
use SprykerEco\Zed\PunchoutGateway\Business\Oci\Session\OciPunchoutSessionExpanderInterface;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\PunchoutQuoteExpander;
use SprykerEco\Zed\PunchoutGateway\Business\Quote\PunchoutQuoteExpanderInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayDependencyProvider;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface getEntityManager()
 */
class PunchoutGatewayBusinessFactory extends AbstractBusinessFactory
{
    public function createPunchoutCxmlSetupRequestProcessor(): PunchoutCxmlSetupRequestProcessorInterface
    {
        return new PunchoutCxmlSetupRequestProcessor(
            $this->getQuoteFacade(),
            $this->getStoreFacade(),
            $this->getCxmlSerializer(),
            $this->getEntityManager(),
            $this->createPunchoutLogger(),
            $this->getRepository(),
        );
    }

    public function createPunchoutOciLoginProcessor(): PunchoutOciLoginProcessorInterface
    {
        return new PunchoutOciLoginProcessor(
            $this->getQuoteFacade(),
            $this->getStoreFacade(),
            $this->getConfig(),
            $this->getEntityManager(),
            $this->createPunchoutLogger(),
            $this->getRepository(),
        );
    }

    public function createPunchoutCxmlSessionStarter(): PunchoutSessionStarterInterface
    {
        return new PunchoutSessionStarter(
            $this->getCustomerFacade(),
            $this->getQuoteFacade(),
            $this->getStoreFacade(),
            $this->getConfig(),
            $this->getEntityManager(),
            $this->createPunchoutLogger(),
            $this->getRepository(),
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
        );
    }

    public function createOciCustomerResolver(): OciCustomerResolverInterface
    {
        return new OciCustomerResolver(
            $this->getCustomerFacade(),
        );
    }

    public function createCxmlPunchoutQuoteExpander(): CxmlPunchoutQuoteExpanderInterface
    {
        return new CxmlPunchoutQuoteExpander();
    }

    public function createCxmlPunchoutSessionExpander(): CxmlPunchoutSessionExpanderInterface
    {
        return new CxmlPunchoutSessionExpander(
            $this->getConfig(),
        );
    }

    public function createCxmlPunchoutQuoteFinder(): CxmlPunchoutQuoteFinderInterface
    {
        return new CxmlPunchoutQuoteFinder(
            $this->getQuoteFacade(),
            $this->getRepository(),
        );
    }

    public function createOciPunchoutSessionExpander(): OciPunchoutSessionExpanderInterface
    {
        return new OciPunchoutSessionExpander();
    }

    public function createPunchoutCxmlAuthenticator(): PunchoutCxmlAuthenticatorInterface
    {
        return new PunchoutCxmlAuthenticator(
            $this->getRepository(),
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

    public function getCxmlSerializer(): Serializer
    {
        return Serializer::create();
    }

    public function createDefaultCxmlContentParser(): DefaultCxmlContentParserInterface
    {
        return new DefaultCxmlContentParser();
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
}
