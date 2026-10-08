# Data and connections

[Deutsch](DATA-de.md)

Website settings: `ddw_bdqn_settings`. Personal preferences: the website-prefixed user option `ddw_bdqn_preferences`. No database tables, content changes or host cron tasks. Deactivation preserves data. Uninstall always removes this host's updater cache; the optional deletion setting removes only this website's settings and personal preferences. Network uninstall follows each website's choice in bounded batches. Breakdance/addon content and user accounts remain.

Library 0.8.1 owns shared network/site options, catalog caches and lifecycle data. Its standard last-host cleanup protects even inactive installed host copies. Its separate deletion choice defaults off and never removes installed plugins or their content.

Updater requests public metadata and release packages from GitHub over HTTPS; platform/version metadata may be sent by WordPress during updates. Library online catalog access defaults off. Enabling it requests public catalog metadata and approved package information from GitHub. No telemetry or analytics. Local icons, CSS and JavaScript are bundled. Resource links connect to their destination only when followed; newsletter URLs contain no personalized account parameters.
