# FAQ by topic

[Deutsch](FAQ-de.md)

## Getting started

### Where are the settings?

Open Breakdance → QuickNav (without an active builder: Settings → Breakdance QuickNav) or use Settings beside Deactivate in the plugin list. Personal preferences are in your profile.

### Why is QuickNav missing?

Check that Breakdance 2.x or 3.x is active in Breakdance mode, the WordPress toolbar is visible and your website/user visibility and permissions allow it. An active Breakdance Navigator suppresses duplicate QuickNav navigation.

## Everyday use & settings

### Will existing constants still work?

Yes. BDQN_NAME_IN_ADMINBAR, BDQN_ICON, BDQN_NUMBER_TEMPLATES, BDQN_VIEW_CAPABILITY, BDQN_ENABLED_USERS and BDQN_DISABLE_FOOTER override saved defaults. Empty legacy user allowlists still deny everyone.

### Can editors see drafts?

Only when unpublished content is enabled and the user has both WordPress object-edit rights and Breakdance edit permission. Setting a lower toolbar capability never grants builder access.

### Which addons are supported?

Existing integrations remain, with verified navigation for the supplied packages and freely available SiteCare, Jooosi Fon, Elements Hive, Express Add On and BreakColorUI Sync. Other premium packages and WPML dependencies await their own checks. See docs/ADDONS.md.

## Compatibility & data

### Does it support Multisite?

Website and network activation are supported. Website settings and personal preferences remain site-scoped. No cross-site toolbar is created. Shared Library data is protected while another host remains installed.

### What happens on uninstall, and is a snippet available?

Settings and preferences are retained by default. The deletion option removes only this website’s QuickNav data; network uninstall applies each website’s choice. Breakdance content remains. A separately generated navigation-only snippet is optional; do not activate it beside the plugin.
