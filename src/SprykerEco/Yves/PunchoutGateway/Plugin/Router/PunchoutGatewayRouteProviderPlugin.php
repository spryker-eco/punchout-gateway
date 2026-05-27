<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutGatewayRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addPunchoutGatewayCxmlSetupRoute($routeCollection);
        $routeCollection = $this->addPunchoutGatewayCxmlStartRoute($routeCollection);
        $routeCollection = $this->addPunchoutGatewayOciSetupRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * @uses \SprykerEco\Yves\PunchoutGateway\Controller\CxmlController::setupAction()
     */
    protected function addPunchoutGatewayCxmlSetupRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(PunchoutGatewayConfig::CXML_SETUP_PREFIX . '/{connectionSlug}', 'PunchoutGateway', 'Cxml', 'setup');
        $routeCollection->add('punchout-gateway-cxml-setup-slug', $route);
        $route = $this->buildPostRoute(PunchoutGatewayConfig::CXML_SETUP_PREFIX, 'PunchoutGateway', 'Cxml', 'setup');
        $routeCollection->add('punchout-gateway-cxml-setup', $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerEco\Yves\PunchoutGateway\Controller\CxmlController::startAction()
     */
    protected function addPunchoutGatewayCxmlStartRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute('/punchout-cxml-start', 'PunchoutGateway', 'Cxml', 'start');
        $routeCollection->add('punchout-gateway-cxml-start', $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerEco\Yves\PunchoutGateway\Controller\OciController::indexAction()
     */
    protected function addPunchoutGatewayOciSetupRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(PunchoutGatewayConfig::OCI_URL_PREFIX . '{connectionSlug}', 'PunchoutGateway', 'Oci', 'index');
        $route->setRequirement('connectionSlug', PunchoutGatewayConfig::OCI_URL_SLUG);
        $routeCollection->add('punchout-gateway-oci-setup-post', $route);

        $route = $this->buildGetRoute(PunchoutGatewayConfig::OCI_URL_PREFIX . '{connectionSlug}', 'PunchoutGateway', 'Oci', 'index');
        $route->setRequirement('connectionSlug', PunchoutGatewayConfig::OCI_URL_SLUG);
        $routeCollection->add('punchout-gateway-oci-setup-get', $route);

        return $routeCollection;
    }
}
