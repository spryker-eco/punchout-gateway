# Project integration

1. \SprykerEco\Zed\PunchoutGateway\Communication\Plugin\Quote\PunchoutSessionQuoteExpanderPlugin into \Pyz\Zed\Quote\QuoteDependencyProvider::getQuoteExpanderPlugins


2. use SprykerEco\Yves\PunchoutGateway\Plugin\Router\PunchoutGatewayRouteProviderPlugin;
new PunchoutGatewayRouteProviderPlugin()

3. Punchout connection request_url is limited to 255 chars
