<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;

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
        $route = $this->buildPostRoute('/punchout-cxml-setup/{connectionSlug}', 'PunchoutGateway', 'Cxml', 'setup');
        $routeCollection->add('punchout-gateway-cxml-setup-slug', $route);
        $route = $this->buildPostRoute('/punchout-cxml-setup', 'PunchoutGateway', 'Cxml', 'setup');
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
        $route = $this->buildPostRoute('/punchout-gateway/oci/{connectionSlug}', 'PunchoutGateway', 'Oci', 'index');
        $route->setRequirement('connectionSlug', '[a-zA-Z0-9_-]+');
        $routeCollection->add('punchout-gateway-oci-setup', $route);

        return $routeCollection;
    }
}
