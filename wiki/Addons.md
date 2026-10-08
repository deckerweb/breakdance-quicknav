# Addon integrations

[Deutsch](ADDONS-de.md)

QuickNav links only to addon administration pages. It does not activate addons, change their settings, validate licenses or request external services while rendering the toolbar. The website settings offer one switch per navigation integration and an optional submenu switch. These switches affect QuickNav alone. Existing websites retain enabled navigation by default. Addons are collected under one Add-ons menu; individual addons may be promoted to the main QuickNav menu with their children. Installed addons appear first; missing and pending entries are collapsed. The four settings sections share one form with a fixed save bar and an unsaved-change indicator. Native registered addon submenus supplement the audited child destinations in admin requests; duplicate or unauthorized links are omitted.

| Integration | Checked package | Destination / scope |
| --- | --- | --- |
| Breakdance AI | 1.2.0 | Existing registered `ai` tab; no duplicate addon entry |
| WPSix Exporter | 2.0.5 | `admin.php?page=wpsix-exporter`; existing legacy integration retained |
| SiteCare Builder Tools | 2.3.0 | Registered Builder Tools page; respects its Breakdance/WordPress Settings parent |
| Jooosi Fon / Yabe Webfont | Jooosi Fon 1.1.5 | Current `jooosi_fon` admin page; previous Yabe detection and destination retained |
| Elements Hive Free | 1.7.1 | Home plus optional Cloudflare Turnstile submenu |
| Elements Hive Pro | 1.7.0 | Home, License and Tools; tested with the actual package |
| Destiny Elements | 1.8.7 (source only) | Native settings plus License; adapter contract simulated, no vendor runtime certification |
| Dancepad | Internally 2.1.0 (source only) | Native Breakdance submenu; adapter contract simulated, no vendor runtime certification |
| Express Add On | 1.4.2 | Express Options plus admin pages exposed by enabled modules in its runtime registry |
| BreakColorUI Sync | 1.3.4 | Local BreakColorUI Sync admin page |
| Breakdance WPML Integration | 1.1.0 | Guarded Template Translations adapter; no link without loaded WPML dependencies. Full WPML integration test pending premium dependency packages |
| WPSix Elements | 1.0.8 | Detected version; supplied package has no separate admin page |
| Image Replacer | 1.0.5 | Detected version; supplied package has no separate admin page |
| Headspin Copilot, Reading Time Calculator, Migration Mode | Existing integrations | Retained; current package tests pending. Migration Mode remains its registered settings tab |

Navigation was checked with Breakdance 2.8.3 and 3.0.0 RC1, including admin/frontend requests, inactive plugins, permission denial and website-scoped switches. This covers QuickNav navigation, not every addon function or licensed builder feature. Jooosi Fon Pro awaits its own package check. The supplied Destiny and Dancepad packages contain code that fixes or replaces license state; those packages were read but not executed. Obtain unchanged manufacturer packages for positive runtime tests. Destiny was a RAR archive with a ZIP filename; version is taken from the plugin header.

BreakMade, Smithy Portal/Connect, Phox Elements and Builder Languages are listed as pending package verification. They produce no guessed detection or menu links. Breakdance Navigator remains a competing-toolbar compatibility case. BreakNav is a comparison candidate, not an automatically added shortcut.

## Sources

- [SiteCare Builder Tools](https://wordpress.org/plugins/sitecare-builder-tools-for-breakdance/)
- [Jooosi Fon / former Yabe Webfont](https://wordpress.org/plugins/yabe-webfont/)
- [Elements Hive](https://wordpress.org/plugins/elements-hive-for-breakdance/)
- [Express Add On](https://wordpress.org/plugins/express-add-on/)
- [Breakdance WPML Integration](https://wordpress.org/plugins/integration-for-breakdance-and-wpml/)
- [BreakColorUI Sync download](https://blocks.breakcolorui.com/plugin-sync/)
- [Headspin documentation](https://headspinui.com/documentation/breakdance-helper/)

## Custom integrations

The `ddw/quicknav/bd_integrations` filter accepts a registry keyed by unique lowercase IDs. Each adapter defines `label`, optional plugin `files` and version `constant`, a boolean `loaded` runtime condition, and `targets` plus optional `children`. Each target defines a validated local page `slug`, admin `file`, required `capability`, boolean `ready` endpoint condition and optional submenu `label`. A registered admin menu's capability and parent are checked again. Missing new admin menus are omitted; frontend requests use only source-verified loaded targets. External URLs and malformed entries are rejected, and duplicate destinations are omitted. Register adapters only for packages whose activation conditions and endpoints have been verified.

## Register your own addon

Register the filter in your addon bootstrap, without requiring QuickNav to be active. The example uses fictional identifiers: replace the plugin file, loader condition and menu slug with your actual values. Return the whole registry and use a unique vendor-prefixed lowercase ID. Discovery may run more than once; keep the callback free of writes and HTTP requests.

```php
// Example in your own addon. Replace the identifiers with your real menu and loader.
add_filter( 'ddw/quicknav/bd_integrations', static function ( $integrations ) {
    $ready = function_exists( 'my_addon_render_settings' );
    $integrations['my-vendor-addon'] = array(
        'label'    => 'My Addon',
        'files'    => array( 'my-addon/my-addon.php' ),
        'loaded'   => $ready,
        'targets'  => array(
            array(
                'slug'       => 'my-addon-settings',
                'file'       => 'admin.php',
                'capability' => 'manage_options',
                'ready'      => $ready,
            ),
        ),
    );
    return $integrations;
} );
```

Existing websites enable a newly registered integration by default. Users can disable it or promote it to the main menu in QuickNav settings. In admin requests, registered child pages under your primary menu are collected automatically. For frontend navigation, declare source-verified `children` using the same target fields plus a translated `label`. Optional `constant` supplies the version; otherwise QuickNav reads the supplied plugin file header. Unavailable loaders and unauthorized destinations produce no links. QuickNav never activates or configures your addon. Do not set internal `legacy`/`fallback` flags for new integrations.
