# Breakdance QuickNav

![Breakdance QuickNav plugin in action](https://raw.githubusercontent.com/deckerweb/breakdance-quicknav/master/assets-github/breakdance-quicknav-screenshot.png)

The **Breakdance QuickNav** plugin adds a quick-access navigator to the WordPress Admin Bar (Toolbar). It allows easy access to Breakdance Builder Templates, Headers, Footers, Global Blocks, Popups, and (regular WordPress) Pages edited with Breakdance, along with other essential settings.

* Contributors: [David Decker](https://github.com/deckerweb), [contributors](https://github.com/deckerweb/breakdance-quicknav/graphs/contributors)
* Tags: breakdance, quicknav, admin bar, toolbar, breakdance builder, site builder, administrators
* Requires at least: 6.7
* Requires PHP: 7.4
* Stable tag: [main](https://github.com/deckerweb/breakdance-quicknav/releases/latest)
* Donate link: https://paypal.me/deckerweb
* License: GPL v2 or later

_Note:_ This plugin was originally developed by [Peter Kulcsár](https://github.com/beamkiller) under the name ["Breakdance Navigator"](https://github.com/beamkiller/breakdance-navigator). Since it is licensed GPL v2 or later, I decided to fork it to add some more/other links and tweak some things. – If you like the original version, fine! Use it and support the original author!

---

## Quick Links 

[Support Project](#support-the-project) | [Installation](#installation) | [Updates](#updates) | [Description](#description) | [How Plugin Works](#how-this-plugin-works) | [FAQ](#frequently-asked-questions) | [Changelog](#changelog) | [Plugin Scope / Disclaimer](#plugin-scope--disclaimer)

---

## Support the Project 

If you find this project helpful, consider showing your support by buying me a coffee! Your contribution helps me keep developing and improving this plugin.

Enjoying the plugin? Feel free to treat me to a cup of coffee ☕🙂 through the following options:

- [![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/W7W81BNTZE)
- [Buy me a coffee](https://buymeacoffee.com/daveshine)
- [PayPal donation](https://paypal.me/deckerweb)
- [Join my **newsletter** for DECKERWEB WordPress Plugins](https://eepurl.com/gbAUUn)

---

## Installation 

#### **Quick Install – as Plugin**
1. **Download ZIP:** [**breakdance-quicknav.zip**](https://github.com/deckerweb/breakdance-quicknav/releases/latest/download/breakdance-quicknav.zip)
2. Upload via WordPress Plugins > Add New > Upload Plugin
3. Once activated, you’ll see the **BD** menu item in the Admin Bar.

#### **Alternative: Use as Code Snippet**
1. Below, download the appropriate snippet version
2. activate or deactivate in your snippets plugin

[**Download .json**](https://github.com/deckerweb/oxygen-quicknav/releases/latest/download/ddw-breakdance-quicknav.code-snippets.json) version for: _Code Snippets_ (free & Pro), _Advanced Scripts_ (Premium), _Scripts Organizer_ (Premium)  
--> just use their elegant script import features  
--> in _Scripts Organizer_ use the "Code Snippets Import"  

For all other snippet manager plugins just use our plugin's main .php file [`breakdance-quicknav.php`](https://github.com/deckerweb/breakdance-quicknav/blob/master/breakdance-quicknav.php) and use its content as snippet (bevor saving your snippet: please check for your plugin if the opening php tag needs to be removed or not!).

--> Please decide for one of both alternatives!

#### Minimum Requirements 
* WordPress version 6.7 or higher
* PHP version 7.4 or higher (better 8.3+)
* MySQL version 8.0 or higher / OR MariaDB 10.1 or higher
* Administrator user with capability `manage_options` and `activate_plugins`

### Tested Compatibility
- **Breakdance Pro**: 2.3.0+ / 2.4.0 Beta
- **WordPress**: 6.7.2 / 6.8 Beta
- **PHP**: 8.0 – 8.3

---

## Updates 

#### For Plugin Version:

1) Alternative 1: Just download a new [ZIP file](https://github.com/deckerweb/breakdance-quicknav/releases/latest/download/breakdance-quicknav.zip) (see above), upload and override existing version. Done.

2) Alternative 2: Use the (free) [**_Git Updater_ plugin**](https://git-updater.com/) and get updates automatically.

3) Alternative 3: Upcoming! – In future I will built-in our own deckerweb updater. This is currently being worked on for my plugins. Stay tuned!

#### For Code Snippet Version:

Just manually: Download the latest Snippet version (see above) and import it in your favorite snippets manager plugin. – You can delete the old snippet; then just activate the new one. Done.

---

## Description 

#### Differences 
**What Does "Breakdance QuickNav" _Different_ than "Breakdance Navigator"?**

_Good question, hehe :-)_

### 1) Intended usage for Administrator users only!
Therefore the default capability to see the new Admin Bar node is set to `activate_plugins`. You can change this via the constant `BDQN_VIEW_CAPABILITY` – define that via `wp-config.php` or via a Code Snippet plugin:
```
define( 'BDQN_VIEW_CAPABILITY', 'edit_posts' );
```

### 2) Restrict to defined user IDs only (since v1.1.0)
You can define an array of user IDs (can also be only _one_ ID) and that way restrict showing the Breakdance Admin Bar item only for those users. Define that via `wp-config.php` or via a Code Snippet plugin:
```
define( 'BDQN_ENABLED_USERS', [ 1, 500, 867 ] );
```
This would enable only for the users with the IDs 1, 500 and 867. Note the square brackets around, and no single quotes, just the ID numbers.

For example you are one of many admin users (role `administrator`) but _only you_ want to show it _for yourself_. Given you have user ID 1:
```
define( 'BDQN_ENABLED_USERS', [ 1 ] );
```
That way only you can see it, the other admins can't!

### 3) Shorter name of main menu item in Admin Bar, just named "BD".
This is way shorter than "Breakdance Nav" and takes much less of the precious space there. However, if you don't enjoy "BD" you can tweak that also via the constant `BDQN_NAME_IN_ADMINBAR` – define that also via `wp-config.php` or via a Code Snippet plugin:
```
define( 'BDQN_NAME_IN_ADMINBAR', 'BD Nav' );
```

### 4) Default icon of main menu item pulled directly from Breakdance plugin.
The yellow default logo icon is awesome but a bit too yellow-ish for my taste – at least within the Admin Bar. Therefore I pull in the builder icon intended for dark mode (light logo on dark background). If that is not there for whatever reason it pulls in Peter's yellow icon (in local plugin folder). You can also tweak that via a constant in `wp-config.php` oder via a Code Snippets plugin:
```
define( 'BDQN_ICON', 'yellow' );
```

### 5) Adjust the number of displayed Templates/ Pages.
The default number of displayed Templates/ Pages got increased to 20 (instead of 10). That means up to 20 items, starting from latest (newest) down to older ones. And, now you can adjust that value via constant in `wp-config.php` or via a Code Snippets plugin:
```
define( 'BDQN_NUMBER_TEMPLATES', 5 );
```
In that example it would only display up to 5 items. NOTE: just add the number, no quotes around it.

### 6) Increased plugin support.
The supported plugins are increased compared to Peters original plugin. The "WPSix Exporter" is now supported by default. All supported plugins are checked if they are active or not.
_Please note:_ I will ONLY add support for direct Breakdance add-on plugins. And I can only add support if I would own a license myself (for testing etc.). Therefore if there might be Breakdance plugins you want me to add integration for, please open an issue on the plugin page on GitHub so we might discuss that. (Thanks in advance!)

### 7) Disable footer items (Links & About)
To disable these menu items, just use another constant in `wp-config.php` or via a Code Snippets plugin:
```
define( 'BDQN_DISABLE_FOOTER', 'yes' );
```

### 8) Updated links.
I carefully updated the links from the Breakdance community, including plugin/ library/ tutorial sites.

### 9) Show Admin Bar also in Block Editor full screen mode.
This an annoyance with WordPress default: the fullscreen mode isn't fullscreen anyways, however, it should at least show the Admin Bar as it makes total sense in this case. – Now it finally does! (since plugin version v1.1.0)

### 10) Alternate Install: Snippet Version!
You can use this "plugin" also as Code Snippet in your favorite snippet manager plugin. See here under ["Installation"](#installation)!

### 11) Other tweaks.
a) There is another check for Breakdance plugin itself: if no Breakdance active then the whole Admin Bar addition is NOT loaded and displayed. Makes sense.

b) If for whatever reason you have already "Breakdance Navigator" installed and ACTIVATED, my plugin (Breakdance QuickNav) will not display anything (even if activated). So it makes sense you decide for one or the other ... 🙂

---

## How this Plugin Works

1. **Pages, Templates, Headers, Footers, Global Blocks, Popups**: Displays up to 10 items, ordered by the last modified date (descending). The "Pages" menu only shows pages built with Breakdance by checking the `_breakdance_data` custom field.
2. **Form Submissions, Design Library, Settings**: Direct links to relevant sections.
3. **Additional Links**: Includes links to resources like the Breakdance website and Facebook group. Some may contain affiliate links.
4. **About**: Includes links to the plugin author.

---

## Frequently Asked Questions 

### How can I change / tweak things?
Please see here under [**Description**](#description) what is possible! (Custom tweaks via constants)

### Why is this functionality not baked into Breakdance itself?
I don't know. Not everything needs to be built-in. That's what plugins are for: those who _need_ this functionality can install and use them.

### Why is this plugin not on wordpress.org plugin repository?
Because the restrictions there for plugin authors are becoming more and more. It would be possible but I don't want that anymore. The same for limited support forums for plugin authors on .org. I have decided to leave this whole thing behind me.

---

## [Changelog](https://github.com/deckerweb/breakdance-quicknav/releases) 

**The Releases**

### 🎉 v1.1.0 – 2025-04-??
* New: Show Admin Bar also in Block Editor full screen mode
* New: Adjust the number of shown Templates / Pages via constant (default: up to 20 - instead of 10) _(new custom tweak)_
* New: Optionally only enable for defined user IDs _(new custom tweak)_
* New: Add info to Site Health Debug, useful for our constants for custom tweaking
* New: Added support for "Breakdance Reading Time Calculator" plugin (third-party)
* New: Added `.pot` file (to translate plugin into your language), plus packaged German translations, including new `l10n.php` files!
* New: Installable and updateable via [Git Updater plugin](https://git-updater.com/)
* Change: Remove packaged icon image file in favor of svg-ed version, inline – makes "plugin" usable as code snippet
* Fix: Minor styling issues for top-level item
* Improved and simplified code to make better maintainable
* Plugin: Add meta links on WP Plugins page
* Alternate install: Use "plugin" as Code Snippet version (see under [Installation](#installation))

### 🎉 v1.0.0 – 2025-03-08
* Initial release
* _Note:_ Forked from "Breakdance Navigator" v1.0.1 by Peter Kulcsár (licensed GPL v2 or later)
* Added support for "Breakdanke Migration" plugin (official add-on)
* Added support for "Yabe Webfont" plugin (third-party; free & Pro version!)
* Added support for "WPSix Exporter" plugin (third-party)
* Improved support for "Breakdance AI Assistant" (official add-on)

---

## Plugin Scope / Disclaimer 

This plugin comes as is. I have no intention to add support for every little detail / third-party plugin / library etc. Its main focus is support for the template types and Breakdance settings. Plugin support is added where it makes sense for the daily work of an Administrator and Site Builder.

_Disclaimer 1:_ So far I will support the plugin for breaking errors to keep it working. Otherwise support will be very limited. Also, it will NEVER be released to WordPress.org Plugin Repository for a lot of reasons.

_Disclaimer 2:_ All of the above might change. I do all this stuff only in my spare time.

_Most of all:_ Have fun building great Breakdance powered sites!!! ;-)

---

Official _Breakdance_ product logo icon: © Soflyy

Icons used in Admin Bar items: [© Remix Icon](https://remixicon.com/)

Icon used in promo graphics: [© Remix Icon](https://remixicon.com/)

Original Copyright: © 2024, Peter Kulcsár  
Readme & Plugin Copyright: © 2025, David Decker – DECKERWEB.de