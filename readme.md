# PunchOut Gateway

[![Latest Stable Version](https://poser.pugx.org/spryker-eco/punchout-gateway/v/stable.svg)](https://packagist.org/packages/spryker-eco/punchout-gateway)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)
[![Documentation](https://img.shields.io/badge/spryker-documentation-cyan)](https://docs.spryker.com/docs/pbc/all/punchout-gateway/integrate-punchout-gateway)

The PunchOut Gateway module provides a basic implementation of OCI and cXML PunchOut flows for Spryker shops. It lets eProcurement systems log a buyer into the shop, build a cart, and transfer that cart back to the buyer's procurement system.

## Supported use cases

- Any number of simultaneously active OCI and cXML connections in a single shop.
- **OCI:** every login creates a fresh `Punchout` cart.
- **cXML:** a single cart is created or reused per `BuyerCookie` value, so a buyer can resume an in-progress cart.
- iframe embedding (global, opt-in — see [Support iframe embedding](#support-iframe-embedding)).

## Architecture at a glance

A PunchOut **connection** (stored in `spy_punchout_connection`) defines one buyer integration: its protocol, store, processor plugin, and field mapping. Connections are managed in the Back Office UI. Each inbound request resolves a **processor plugin** at runtime by the FQCN stored on the connection. The flow:

1. Buyer's system authenticates (OCI credential or cXML shared secret).
2. The shop resolves a customer and a quote, logs the buyer in, and persists a `spy_punchout_session`.
3. Buyer shops; the **Transfer Cart** widget posts the cart back to the buyer's procurement system.

---

## Installation

### 1. Install the module

```bash
composer require spryker-eco/punchout-gateway:^1.0.0
```

### 2. Configure the module

Optional logging via AWS Parameter Store.

`config/Shared/config_default.php`

```php
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

$config[PunchoutGatewayConstants::ENABLE_LOGGING] = getenv('PUNCHOUT_GATEWAY_ENABLE_LOGGING') ?: false;
```

| Constant | Description | Default |
|----------|-------------|---------|
| `ENABLE_LOGGING` | Enables or disables logging for PunchOut Gateway. | `false` |

When enabled, the module emits structured entries through `PunchoutLoggerInterface` (request reception/parsing, authentication, response generation, quote/session creation, uncaught throwables). When disabled, the resolver returns `NullPunchoutLogger` and all calls are no-ops.

### 3. Additional module configuration

`src/Pyz/Zed/PunchoutGateway/PunchoutGatewayConfig.php`:

| Method | Default | Description |
|--------|---------|-------------|
| `isLoggingEnabled()` | `false` | Enables or disables PunchOut Gateway logging. |
| `getCxmlSessionStartUrlValidityInSeconds()` | `600` | Validity (s) of the cXML session start URL. Bounds 0–3600. |
| `getOciDefaultStartUrl()` | `'/'` | Default redirect URL after OCI session start. |
| `getCxmlSessionTokenLength()` | `32` | Length of the generated cXML session token. Bounds 16–128. |

These values can also be changed at runtime in the Back Office under *Configuration > Punchout Gateway*.

### 4. Update Quote configuration

Allow the PunchOut session field to be saved with the quote.

`src/Pyz/Zed/Quote/QuoteConfig.php`

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

### 5. Set up the database schema

```bash
vendor/bin/console propel:install
```

Creates:

| Table | Description |
|-------|-------------|
| `spy_punchout_connection` | PunchOut connection configuration per store. |
| `spy_punchout_credential` | Credentials (username/password) linked to a connection and customer. |
| `spy_punchout_session` | Active PunchOut sessions linked to a quote. |

### 6. Generate transfer objects

```bash
vendor/bin/console transfer:generate
```

### 7. Register plugins

**Quote expander** — `src/Pyz/Zed/Quote/QuoteDependencyProvider.php`

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

**Route provider** — `src/Pyz/Yves/Router/RouterDependencyProvider.php`

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

**Security header expander** — `src/Pyz/Yves/Application/ApplicationDependencyProvider.php`

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

#### Support iframe embedding

If your eProcurement system embeds the shop in an iframe, set this in the deploy file for each environment:

```yml
image:
  environment:
    SPRYKER_YVES_SESSION_COOKIE_SAMESITE: 'none'
```

iframe headers are emitted per connection via the **Allow iFrame** flag (or whenever a `~TARGET` form field is sent).

### 8. Register the cart widget

`src/Pyz/Yves/ShopApplication/ShopApplicationDependencyProvider.php`

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

Embed the widget in the cart template (stock `spryker-shop/cart-page`: `SprykerShop/Yves/CartPage/Theme/default/templates/page-layout-cart/page-layout-cart.twig`, or your override) so the **Transfer Cart** button shows on the cart page:

```twig
{% raw %}
{% widget 'PunchoutCartWidget' args [data.cart] only %}{% endwidget %}
{% endraw %}
```

### 9. Import glossary data

```bash
# Option 1 — module config file
vendor/bin/console data:import --config=vendor/spryker-eco/punchout-gateway/data/import/punchout-gateway.yml

# Option 2 — copy vendor/spryker-eco/punchout-gateway/data/import/*.csv into
# data/import/common/common/, then:
vendor/bin/console data:import glossary
```

Back Office Zed translations ship in `data/translation/Zed/en_US.csv` and `de_DE.csv` and are picked up by the standard Zed translator — no separate import. Override a label by adding the same key to your project's Zed translation file.

### Verify the integration

- Open *Punchout Connections* in the Back Office — the grid renders empty until you create a connection.
- Run the demo command and confirm `spy_punchout_connection` and the grid reflect the demo cXML and OCI connections for store `DE`:

```bash
vendor/bin/console punchout-gateway:demo-connection:create
```

Do not use demo data in production.

---

## Manage connections (Back Office)

Open *Punchout Connections*. The grid lists every connection across all stores with *View*, *Edit*, *Activate*/*Deactivate*, and *Delete* actions.

### Create a connection

Common fields:

| Field | Notes |
|-------|-------|
| **Connection Name** | Human-readable label. Required, up to 255 chars, not unique. |
| **Store** | Store the buyer must be logged in to. |
| **Protocol Type** | `oci` or `cxml`. Cannot be changed after creation. |
| **Processor Plugin Class** | FQCN of a processor plugin. The dropdown only offers plugins whose `getType()` matches the protocol. |
| **Active** | When unchecked, requests to this connection are rejected. |
| **Allow iFrame** | When checked, the Storefront emits iframe-friendly CSP headers while the session is active. |

Protocol-specific fields appear dynamically:

**cXML**

| Field | Notes |
|-------|-------|
| **Sender Identity** | Must be unique. Matched against the buyer's `Header/Sender/Credential/Identity`. |
| **Sender Shared Secret** | Stored hashed (`password_hash()`); incoming `SharedSecret` verified with `password_verify()`. |

cXML request URL is fixed at `/punchout-cxml-setup`, optionally followed by a slug — post to `/punchout-cxml-setup/<slug>` to target a specific connection.

**OCI**

| Field | Notes |
|-------|-------|
| **Request URL** | Slug appended to `/punchout-gateway/oci/`. Only `_`, `-`, letters, digits allowed. |
| **Form Method** | `POST` or `GET` — method the buyer uses to submit the login form. |
| **Username Field Name** | Form field carrying the username. Default `USERNAME`. |
| **Password Field Name** | Form field carrying the password. Default `PASSWORD`. |

### Edit / activate / delete

On *Edit*, **Protocol Type** is read-only; for cXML the **Sender Shared Secret** is blank — leave blank to keep, type to rotate. Use *Activate*/*Deactivate* to toggle without opening the form. *Delete* cascades — it removes every credential and session of the connection, ending in-flight carts.

### Manage credentials (OCI only)

Credentials map a username/password pair to a Spryker customer. On the connection's *View* page, select *Add credential*:

| Field | Notes |
|-------|-------|
| **Username** | Sent by the buyer in the `usernameField` form field. |
| **Password** / **Repeat Password** | Stored as `password_hash()`. Leave blank on edit to keep the existing hash. |
| **Customer ID** | The Spryker customer logged in on successful auth. |
| **Active** | When unchecked, the credential is rejected even on matching username/password. |

cXML connections need no credentials — the customer is identified by the `UserEmail` extrinsic. For both protocols, customers must be fully configured in the shop so that only permitted products and prices are accessible.

---

## Field mapping

Each connection can override how individual outbound protocol fields are populated, without a custom processor plugin. Mappings are stored in the connection's `configuration` JSON under `mapping` and edited in the Back Office connection form (available only when editing, not creating).

`mapping` is an object of `targetField: sourceExpression` pairs. OCI example:

```json
{
  "mapping": {
    "NEW_ITEM-DESCRIPTION": "item.name",
    "NEW_ITEM-VENDORMAT": "item.sku&\"_DE\"",
    "NEW_ITEM-LONGTEXT": "item.description"
  }
}
```

- **Target field** — for OCI a `NEW_ITEM-*` field; for cXML a full cXML path (e.g. `cXML.Message.PunchOutOrderMessage.ItemIn.ItemDetail.Description`) or a custom extrinsic.
- **Source expression** — where the value comes from at cart-return time. Required fields fall back to their default when unmapped; optional fields are emitted only when mapped.

### Source expression syntax

| Form | Example | Result |
|------|---------|--------|
| Plugin expression | `item.sku` | Value read from a field-mapper plugin keyed `item`, path `sku`. |
| Quoted constant | `"EA"` / `'EA'` | The literal text. |
| Concatenation | `item.sku&"_suffix"` | Segments joined with `&`, resolved individually then concatenated. |
| Forced empty | `""` | An explicit empty value. |

Expressions are evaluated at cart-return time by `FieldValueResolver`. The Back Office source input is an autosuggest served by `/punchout-gateway/source-field-suggestions/index`.

### Field-mapper plugins

Source roots are provided by plugins implementing `PunchoutFieldMapperPluginInterface`, registered keyed by `pluginKey` in `Service\PunchoutGateway\PunchoutGatewayDependencyProvider::getFieldMapperPlugins()`. Shipped:

| Key | Plugin | Source transfer |
|-----|--------|-----------------|
| `item` | `ItemTransferFieldMapperPlugin` | `ItemTransfer` of the current cart item. |
| `quote` | `QuoteTransferFieldMapperPlugin` | `QuoteTransfer` of the cart. |

To add a root (e.g. `company`), implement the interface and register it under a new key:

`src/Pyz/Service/PunchoutGateway/PunchoutGatewayDependencyProvider.php`

```php
namespace Pyz\Service\PunchoutGateway;

use Pyz\Service\PunchoutGateway\Plugin\FieldMapper\CompanyTransferFieldMapperPlugin;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayDependencyProvider as SprykerEcoPunchoutGatewayDependencyProvider;

class PunchoutGatewayDependencyProvider extends SprykerEcoPunchoutGatewayDependencyProvider
{
    protected function getFieldMapperPlugins(): array
    {
        return [
            ...parent::getFieldMapperPlugins(),
            'company' => new CompanyTransferFieldMapperPlugin(),
        ];
    }
}
```

### Custom extrinsics (cXML)

You can map values to custom `Extrinsic` elements in each `ItemIn`. The name must match `^[A-Za-z0-9_]+$` and must not be a reserved buyer-identity name: `User`, `UniqueUsername`, `UniqueName`, `UserId`, `UserEmail`, `UserFullName`, `UserPrintableName`, `FirstName`, `LastName`, `PhoneNumber`, `UserPhoneNumber`. These are also stripped from echoed extrinsics to avoid leaking PII.

---

## Endpoints

`PunchoutGatewayRouteProviderPlugin` registers:

| Method | Path | Purpose |
|--------|------|---------|
| `POST` | `/punchout-cxml-setup` | Inbound cXML `PunchOutSetupRequest`. |
| `GET` | `/punchout-cxml-start?session={token}` | Buyer's browser opens this with the token from the synchronous `PunchOutSetupResponse`. |
| `POST` | `/punchout-gateway/oci/{connectionSlug}` | Inbound OCI login form (default method). |
| `GET` | `/punchout-gateway/oci/{connectionSlug}` | Inbound OCI login when the connection uses `formMethod=GET`. |

The OCI slug matches `[a-zA-Z0-9_-]+`.

### cXML session start lifecycle

Two-step handshake:

1. Buyer `POST`s a `PunchOutSetupRequest`. The shop authenticates, resolves customer and quote, persists a `spy_punchout_session`, and replies synchronously with a `PunchOutSetupResponse` whose `StartPage/URL` carries a one-shot token: `https://<shop-domain>/punchout-cxml-start?session=<token>`.
2. Buyer's browser follows that URL. `CxmlController::startAction` reads the token, looks up the session, logs the customer in, persists the protocol CSP fragment, and redirects to the shop.

---

## Protocol coverage

cXML and OCI cover a broad range of features; the table below summarizes what the default flow parses and emits. Elements not listed are not interpreted by the default flow (cXML inbound extras are collected into `extrinsicFields`; OCI inbound extras are preserved in `PunchoutOciLoginRequestTransfer.formData`). Full field-by-field mapping: [PunchOut Protocols Coverage](https://docs.spryker.com/docs/pbc/all/punchout-gateway/punchout-protocol-coverage.html).

### cXML — received (buyer → Spryker)

`PunchOutSetupRequest` is parsed by `DefaultCxmlContentParser` into `PunchoutCxmlSetupRequestTransfer`. Key elements:

- **Header:** `@payloadID`, `@timestamp`, `From/To/Sender Credential/Identity`, `Sender/Credential/SharedSecret` (verified against the connection hash).
- **Payload:** `@operation` (`create`/`edit`), `BuyerCookie`, `BrowserFormPost/URL`, `Extrinsic` (collected as key/value map).
- **ShipTo/Address** and, only for `operation="edit"`, the `ItemOut` list (mapped to `PunchoutItemTransfer`).

### cXML — returned (Spryker → buyer)

- `PunchOutSetupResponse` — synchronous reply (HTTP 200, `text/xml`) with `StartPage/URL` carrying the session token. On error, a `Status` document with a non-200 code is returned instead.
- `PunchOutOrderMessage` — cart return, POSTed to `BrowserFormPost.URL`. Per item it emits SKU, quantity, name, unit price, `EA` unit of measure, classification, and optional fields when mapped. Header carries `BuyerCookie`, totals (currency/shipping/tax), and `ShipTo` address. Extrinsics are echoed back with the deny-list keys removed.

### OCI — received (buyer → Spryker)

| Field | Required | Purpose |
|-------|----------|---------|
| `USERNAME` | Yes | Buyer user. Field name configurable via `usernameField`. Matched against `spy_punchout_credential.username`. |
| `PASSWORD` | Yes | Authenticates the buyer. Field name configurable via `passwordField`. Verified against the stored hash. |
| `HOOK_URL` | Yes | Cart-return target. Must start with `https://`. Stored as `browserFormPostUrl`. |
| `~TARGET` | No | Frame target echoed back to the buyer. |
| `~OkCode`, `~CALLER` | No | SAP control fields. |

### OCI — returned (Spryker → buyer)

A `Transfer Cart` HTML form whose `action` is the received `HOOK_URL` (and `target` is `~TARGET`). Per item: `NEW_ITEM-DESCRIPTION[N]` (name), `NEW_ITEM-QUANTITY[N]`, `NEW_ITEM-UNIT[N]` (`EA`), `NEW_ITEM-PRICE[N]` (unit price), `NEW_ITEM-CURRENCY[N]`, `NEW_ITEM-VENDORMAT[N]` (SKU). `~OkCode`/`~CALLER` are echoed when present. Any `NEW_ITEM-*` source is overridable per connection via field mapping.

---

## Extension points

### Processor plugin

Each connection resolves its processor plugin at runtime by the FQCN in `spy_punchout_connection.processor_plugin_class`. No DI registration required. Implement:

- OCI — `PunchoutProcessorPluginInterface`
- cXML — `PunchoutCxmlProcessorPluginInterface`

Defaults: `DefaultOciProcessorPlugin`, `DefaultCxmlProcessorPlugin`. The simplest customization is to extend a default and override only what you need, then point the connection's `processor_plugin_class` at your class:

```php
namespace Pyz\Zed\ProjectPunchoutGateway\Communication\Plugin\PunchoutGateway;

use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;

class CustomOciProcessorPlugin extends DefaultOciProcessorPlugin
{
    // Override only the methods you need.
}
```

Lifecycle methods:

| Method | Called when | Functionality |
|--------|-------------|---------------|
| `getType` | At plugin resolution | Reports the protocol (`oci`/`cxml`) this plugin handles. |
| `authenticate` | First login step | Finds a connection from the setup request; `null` if none. |
| `resolveCustomer` | After connection found | Finds the customer; `null` if none. |
| `resolveQuote` | After customer resolved | Creates or reuses a quote. |
| `expandQuote` | After quote resolved | Adjusts the quote post-PunchOut prep. |
| `resolveSession` | Before session persisted | Additional session validation. |
| `parseCxmlRequest` *(cXML)* | After XML parsed, connection found | Extra mapping of cXML data onto the setup request. |
| `expandResponse` *(cXML)* | After session creation | Expands the login response (e.g. default start URL). |

### Default behavior (out of the box)

Both default plugins use `QuoteCreator` to stamp **Store** (from `fk_store`) and **Currency** (store default) on every new quote.

- **OCI** — customer comes entirely from the matched credential record (`PunchoutOciAuthenticator` → `connection.idCustomer`); every login starts a fresh empty cart; items are not carried on login — they travel back via the Transfer Cart POST.
- **cXML** — customer resolved from the `UserEmail` extrinsic; quote reused per `BuyerCookie` (recreated if it belongs to a different store); items are mapped from `ItemOut` on `operation="edit"`.

### Form handler plugin

The Transfer Cart form is built by `PunchoutFormHandlerPluginInterface` implementations; `PunchoutFormDataBuilder::build()` returns the first whose `isApplicable()` is `true`. Defaults: `DefaultOciPunchoutFormHandlerPlugin`, `DefaultCxmlPunchoutFormHandlerPlugin`. Register a custom handler **before** the defaults so it takes precedence for its protocol:

`src/Pyz/Yves/PunchoutGateway/PunchoutGatewayDependencyProvider.php`

```php
namespace Pyz\Yves\PunchoutGateway;

use Pyz\Yves\ProjectPunchoutGateway\Plugin\Form\CustomCxmlPunchoutFormHandlerPlugin;
use SprykerEco\Yves\PunchoutGateway\PunchoutGatewayDependencyProvider as SprykerEcoPunchoutGatewayDependencyProvider;

class PunchoutGatewayDependencyProvider extends SprykerEcoPunchoutGatewayDependencyProvider
{
    protected function getPunchoutFormHandlerPlugins(): array
    {
        return [
            new CustomCxmlPunchoutFormHandlerPlugin(),
            ...parent::getPunchoutFormHandlerPlugins(),
        ];
    }
}
```

The Twig template renders the form only when `formData` is non-null and `actionUrl` is non-empty. For OCI the button shows even for an empty cart (to allow empty-order return); for cXML no button renders for an empty cart (to be improved).

### Security header expander plugin

At session start, Yves applies protocol-specific CSP directives via `PunchoutSecurityHeaderExpanderPluginInterface` implementations so the Storefront can be embedded and post back. Default: `DefaultOciSecurityHeaderExpanderPlugin` (adds `frame-ancestors` for OCI sessions carrying `~TARGET`). CSP headers are included when `allow_iframe` is `true`. Register a custom expander via `getPunchoutSecurityHeaderExpanderPlugins()`.

### Session-in-quote expander plugin

`PunchoutQuoteExpander` runs the session through every registered `PunchoutSessionInQuoteExpanderPluginInterface` before stamping it on the `QuoteTransfer`. Use it to enrich or override session fields based on the quote before it is persisted with the cart.

---

## Documentation

- [Integrate PunchOut Gateway](https://docs.spryker.com/docs/pbc/all/punchout-gateway/integrate-punchout-gateway.html)
- [Manage PunchOut connections](https://docs.spryker.com/docs/pbc/all/punchout-gateway/manage-punchout-connections.html)
- [Project configuration for PunchOut Gateway](https://docs.spryker.com/docs/pbc/all/punchout-gateway/project-configuration-for-punchout-gateway.html)
- [PunchOut Protocols Coverage](https://docs.spryker.com/docs/pbc/all/punchout-gateway/punchout-protocol-coverage.html)
