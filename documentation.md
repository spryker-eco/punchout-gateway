# Project integration

1. \SprykerEco\Zed\PunchoutGateway\Communication\Plugin\Quote\PunchoutSessionQuoteExpanderPlugin into \Pyz\Zed\Quote\QuoteDependencyProvider::getQuoteExpanderPlugins

2. into \Pyz\Yves\Router\RouterDependencyProvider::getRouteProvider
   use SprykerEco\Yves\PunchoutGateway\Plugin\Router\PunchoutGatewayRouteProviderPlugin;
   new PunchoutGatewayRouteProviderPlugin()
