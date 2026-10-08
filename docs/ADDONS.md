# Addon integrations

[Deutsch](ADDONS-de.md)

QuickNav links only to addon administration pages. It does not activate addons, change their settings, validate licenses or request external services while rendering the toolbar. The website settings offer one switch per navigation integration and an optional submenu switch. These switches affect QuickNav alone. Existing websites retain enabled navigation by default.

| Integration | Checked package | Destination / scope |
| --- | --- | --- |
| Breakdance AI | 1.2.0 | Existing registered `ai` tab; no duplicate addon entry |
| WPSix Exporter | 2.0.5 | `admin.php?page=wpsix-exporter`; existing legacy integration retained |
| SiteCare Builder Tools | 2.3.0 | Registered Builder Tools page; respects its Breakdance/WordPress Settings parent |
| Jooosi Fon / Yabe Webfont | Jooosi Fon 1.1.5 | Current `jooosi_fon` admin page; previous Yabe detection and destination retained |
| Elements Hive Free | 1.7.1 | Home plus optional Cloudflare Turnstile submenu |
| Express Add On | 1.4.2 | Express Options plus admin pages exposed by enabled modules in its runtime registry |
| BreakColorUI Sync | 1.3.4 | Local BreakColorUI Sync admin page |
| Breakdance WPML Integration | 1.1.0 | Guarded Template Translations adapter; no link without loaded WPML dependencies. Full WPML integration test pending premium dependency packages |
| WPSix Elements | 1.0.8 | Detected version; supplied package has no separate admin page |
| Image Replacer | 1.0.5 | Detected version; supplied package has no separate admin page |
| Headspin Copilot, Reading Time Calculator, Migration Mode | Existing integrations | Retained; current package tests pending. Migration Mode remains its registered settings tab |

Navigation was checked with Breakdance 2.8.3 and 3.0.0 RC1, including admin/frontend requests, inactive plugins, permission denial and website-scoped switches. This covers QuickNav navigation, not every addon function or licensed builder feature. Elements Hive Pro and Jooosi Fon Pro await their own package checks.

BreakMade, Dancepad, Smithy Portal/Connect, Phox Elements and Builder Languages are listed as pending package verification. They produce no guessed detection or menu links. Breakdance Navigator remains a competing-toolbar compatibility case. BreakNav is a comparison candidate, not an automatically added shortcut.

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
