<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\PunchoutGateway;

use CXml\Serializer;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Quote\QuoteClientInterface;
use Spryker\Client\Session\SessionClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use SprykerEco\Client\PunchoutGateway\PunchoutGatewayClientInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\NullPunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerEco\Yves\PunchoutGateway\Model\LoginModel;
use SprykerEco\Yves\PunchoutGateway\Model\LoginModelInterface;
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
            $this->createCxmlSerializer(),
        );
    }

    public function createCxmlSerializer(): Serializer
    {
        return Serializer::create();
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

    public function getQuoteClient(): QuoteClientInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::CLIENT_QUOTE);
    }
}
