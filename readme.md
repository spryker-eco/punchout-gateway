# Punchout Gateway

[![Latest Stable Version](https://poser.pugx.org/spryker-eco/punchout-gateway/v/stable.svg)](https://packagist.org/packages/spryker-eco/punchout-gateway)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)
[![Documentation](https://img.shields.io/badge/spryker-documentation-cyan)](https://docs.spryker.com/docs/pbc/all/punchout-gateway/integrate-punchout-gateway)


Punchout Gateway module enables project to handle eProcurement requests to build a cart for customers.

## 1. Install the module

Install the Punchout Gateway module using Composer:

```bash
composer require spryker-eco/punchout-gateway:^0.1.0
```

## 2. Configure the module

To control logging through the AWS Parameter Store, add the following optional configuration:

``config/Shared/config_default.php``

```php
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

$config[PunchoutGatewayConstants::ENABLE_LOGGING] = getenv('PUNCHOUT_GATEWAY_ENABLE_LOGGING') ?? false;
```

### Configuration constants

| Constant | Description                                                                                                                                     | Default  |
|----------|-------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| `ENABLE_LOGGING` | Enables or disables logging for Punchout Gateway. Check \SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLogger to learn what is being logged. | `false` |

## 3. Additional module configuration

`src/Pyz/Zed/PunchoutGateway/PunchoutGatewayConfig.php` provides the following configuration methods:

| Method | Default | Description |
|--------|---------|-------------|
| `isLoggingEnabled()` | `true` | Enables or disables Punchout Gateway logging. |
| `getCxmlSessionStartUrlValidityInSeconds()` | `600` | Validity period of the cXML session start URL in seconds. |
| `getOciDefaultStartUrl()` | `'/'` | Default redirect URL after OCI session start. |
| `getCxmlSessionTokenLength()` | `32` | Length of the generated cXML session token. |

## 4. Update Quote configuration

Update `QuoteConfig` to allow the Punchout session field to be saved with the quote

``src/Pyz/Zed/Quote/QuoteConfig.php``

```php
use Generated\Shared\Transfer\QuoteTransfer;

public function getQuoteFieldsAllowedForSaving(): array
{
    return array_merge(parent::getQuoteFieldsAllowedForSaving(), [
        // ...
        QuoteTransfer::PUNCHOUT_SESSION,
    ]);
}
```

## 5. Set up the database schema

Install the database schema:

```bash
vendor/bin/console propel:install
```

This creates the following tables:

| Table | Description |
|-------|-------------|
| `spy_punchout_connection` | Stores Punchout connection configuration per store. |
| `spy_punchout_credential` | Stores credentials (username/password) linked to a connection and customer. |
| `spy_punchout_session` | Stores active Punchout sessions linked to a quote. |

## 6. Generate transfer objects

Generate transfer objects for the module:

```bash
vendor/bin/console transfer:generate
```

## 7. Register plugins

### Register the Quote expander plugin

Add the Punchout session expander plugin:

``src/Pyz/Zed/Quote/QuoteDependencyProvider.php``

```php
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\Quote\PunchoutSessionQuoteExpanderPlugin;

protected function getQuoteExpanderPlugins(): array
{
    return [
        // ...
        new PunchoutSessionQuoteExpanderPlugin(),
    ];
}
```

### Register the route provider plugin

Add the route provider plugin

``src/Pyz/Yves/Router/RouterDependencyProvider.php``

```php
use SprykerEco\Yves\PunchoutGateway\Plugin\Router\PunchoutGatewayRouteProviderPlugin;

protected function getRouteProvider(): array
{
    return [
        // ...
        new PunchoutGatewayRouteProviderPlugin(),
    ];
}
```

### Register the security header expander plugin

Add the security header expander plugin

``src/Pyz/Yves/Application/ApplicationDependencyProvider.php``

```php
use SprykerEco\Yves\PunchoutGateway\Plugin\Application\PunchoutSecurityHeaderExpanderPlugin;

protected function getSecurityHeaderExpanderPlugins(): array
{
    return [
        // ...
        new PunchoutSecurityHeaderExpanderPlugin(),
    ];
}
```

## 8. Register the cart widget

Add the Punchout cart widget

``src/Pyz/Yves/ShopApplication/ShopApplicationDependencyProvider.php``

```php
use SprykerEco\Yves\PunchoutGateway\Widget\PunchoutCartWidget;

protected function getGlobalWidgets(): array
{
    return [
        // ...
        PunchoutCartWidget::class,
    ];
}
```

If you have custom Yves templates or your own frontend, add `PunchoutCartWidget` to your cart template. The core template is located at `SprykerShop/Yves/CartPage/Theme/default/templates/page-layout-cart/page-layout-cart.twig`.

The following example shows `PunchoutCartWidget` usage:

```twig
{% raw %}
{% widget 'PunchoutCartWidget' args [data.cart] only %}{% endwidget %}
{% endraw %}
```

## 9. Import glossary data

The module provides translation data for tax validation messages.

**Option 1: Import using the module's configuration file**

```bash
vendor/bin/console data:import --config=vendor/spryker-eco/punchout-gateway/data/import/punchout-gateway.yml
```

**Option 2: Copy file content and import individually**

Copy content from `vendor/spryker-eco/punchout-gateway/data/import/*.csv` to the corresponding files in `data/import/common/common/`. Then run:

```bash
vendor/bin/console data:import glossary
```

## 10. PunchOut connection configuration

Since UI for connection setup is not ready yet, we provide these 2 console commands to create demo configuration:

- OCI flow:

```bash
  vendor/bin/console punchout-gateway:oci:demo-connection:create
```

- cXML flow:

```bash
  vendor/bin/console punchout-gateway:cxml:demo-connection:create
```

### 10.1 OCI connection configuration

In order to configure OCI connection you have to create an entry in the table `spy_punchout_connection`:

| Column | Value                                                | Comments                                                                                                                                                                                           |
|--------|------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `fk_store` | Store id (e.g. DE)                                   |                                                                                                                                                                                                    |
| `name` | Human-readable label                                 | Not used anywhere, only for readability                                                                                                                                                            |
| `is_active` | `true`                                               | Determines if the connection can be used.                                                                                                                                                          |
| `allow_iframe` | `true` when the buyer embeds Spryker in an iframe    | Enforces iframe-specific headers, when the PunchOut session is active. If **~TARGET** is sent during request, headers are sent regardless of this value.                                           |
| `protocol_type` | `PunchoutGatewayConfig::PROTOCOL_TYPE_OCI` (`'oci'`) | Has to be `oci` to work correctly.                                                                                                                                                                 |
| `request_url` | Endpoint path the buyer posts the OCI login form to  | This URL without a domain is the unique identifier of each connection, and can be anything that starts with ``https://<shop-domain>/punchout-gateway/oci/``, i.e. /punchout-gateway/oci/my-company |
| `configuration` | JSON configuration                                   | See below `OCI Login configuration`                                                                                                                                                                |
| `processor_plugin_class` | Full class name of the processor plugin.             | `DefaultOciProcessorPlugin::class` or project subclass                                                                                                                                             |

The pair `fk_store` and `request_url` must be unique.

#### OCI Login configuration
Field `configuration` contains a JSON, with the following handled keys. They are all optional, override only when different from defaults.

| Key | Default | Purpose                                                     |
|-----|---------|-------------------------------------------------------------|
| `usernameField` | `USERNAME` | Form field name carrying the username during login request. |
| `passwordField` | `PASSWORD` | Form field name carrying the password during login request. |


### 10.2 cXML connection

Columns used on `spy_punchout_connection`:

| Column | Value |
|--------|-------|
| `fk_store` | Store id |
| `name` | Human-readable label |
| `is_active` | `true` |
| `protocol_type` | `PunchoutGatewayConfig::PROTOCOL_TYPE_CXML` (`'cxml'`) |
| `sender_identity` | cXML `From/Credential/Identity` value (globally unique) |
| `configuration` | JSON carrying `senderSharedSecret` |
| `processor_plugin_class` | `DefaultCxmlProcessorPlugin::class` or project subclass |

`configuration` JSON keys:

| Key | Required | Purpose |
|-----|----------|---------|
| `senderSharedSecret` | yes | Shared secret used to authenticate the cXML `PunchOutSetupRequest` |

No row in `spy_punchout_credential` is needed — cXML auth uses `sender_identity` + `senderSharedSecret`, not username/password.

Example (excerpt from `PunchoutCxmlDemoConnectionCreateConsole`):

```php
$entity = new SpyPunchoutConnection();
$entity->setFkStore($storeTransfer->getIdStoreOrFail());
$entity->setName('Demo cXML Connection');
$entity->setIsActive(true);
$entity->setProtocolType(PunchoutGatewayConfig::PROTOCOL_TYPE_CXML);
$entity->setSenderIdentity('MyNewIdentity');
$entity->setConfiguration(json_encode([
    'senderSharedSecret' => 'jd8je3$ndP',
]));
$entity->setProcessorPluginClass(DefaultCxmlProcessorPlugin::class);
$entity->save();
```

Uniqueness: `sender_identity` MUST be globally unique — each cXML buyer identity maps to exactly one connection.

> **Security note:** Store `senderSharedSecret` and credentials in project secret storage for real deployments. Do not commit production values.

## 11. PunchOut flow processor plugin

Each punchout connection resolves its processor plugin at runtime using the fully qualified class name stored in the `spy_punchout_connection.processor_plugin_class`.
The plugin has to implement :
- for OCI flow - `\SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutProcessorPluginInterface`
- for cXML flow - `\SprykerEco\Zed\PunchoutGateway\Dependency\Plugin\PunchoutCxmlProcessorPluginInterface`.

This module provides default functionality:
- \SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin - for cXML flow,
- \SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin - for OCI flow.

No dependency injection registration is required, the plugin is loaded at runtime.

### Create a custom plugin

Place the plugin in your project's Zed communication layer:

`src/Pyz/Zed/PunchoutGateway/Communication/Plugin/PunchoutGateway/CustomOciProcessorPlugin.php`

The simplest approach is to extend the default OCI plugin and override only the methods you need:

```php
namespace Pyz\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway;

use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;

class CustomOciProcessorPlugin extends DefaultOciProcessorPlugin
{
    // Override only the methods you need to customise.
}
```

### Plugin methods

| Method | Called when | Default OCI behaviour | Override to |
|--------|-------------|----------------------|-------------|
| `authenticate(PunchoutSetupRequestTransfer): ?PunchoutConnectionTransfer` | First step of the login flow | Reads username/password field names from the connection, verifies them against `spy_punchout_credential`. Returns the enriched connection transfer (with `idCustomer`) on success, `null` on failure. | Change credential lookup strategy, support SSO, add extra verification steps. |
| `resolveCustomer(PunchoutSetupRequestTransfer): ?CustomerTransfer` | After successful authentication | Loads the customer from the database using the `idCustomer` that was resolved during authentication. Returns `null` if the customer cannot be found. | Map to a different customer, create the customer on the fly, or enrich the transfer with additional data. |
| `resolveQuote(PunchoutSetupRequestTransfer): QuoteTransfer` | After customer is resolved | Creates a new, empty quote with a default name. Never reuses an existing quote. | Load an existing in-progress quote for the session, apply store/currency defaults, or seed the quote with items. |
| `expandQuote(QuoteTransfer, PunchoutSetupRequestTransfer): QuoteTransfer` | After the quote is resolved | No-op — returns the quote unchanged. | Add connection-specific quote fields, set price mode, apply discounts, or attach custom data from the OCI form post. |
| `resolveSession(PunchoutSessionTransfer, PunchoutSetupRequestTransfer, QuoteTransfer): ?PunchoutSessionTransfer` | After the quote is expanded, before the session is persisted | Sets operation to `CREATE` and extracts the `hook_url` from the OCI form post to use as the browser form post URL. Returns `null` to abort session creation. | Change the operation, override the return URL, or populate custom session fields. |

### Register the custom plugin on a connection

Set the fully qualified class name of your plugin on the connection record:

```sql
UPDATE spy_punchout_connection
SET processor_plugin_class = '\\Pyz\\Zed\\PunchoutGateway\\Communication\\Plugin\\PunchoutGateway\\CustomOciProcessorPlugin'
WHERE name = 'my-connection-name';
```


TODO:  document what we send back for cXML/OCI by default
