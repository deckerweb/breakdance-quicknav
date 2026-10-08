<?php
/**
 * Plugin Name: Breakdance QuickNav
 * Plugin URI: https://github.com/deckerweb/breakdance-quicknav
 * Description: Quick access to Breakdance content, templates, settings, and official add-ons from the WordPress toolbar.
 * Version: 2.0.0-beta.1
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Author: David Decker – DECKERWEB
 * Author URI: https://github.com/deckerweb
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: breakdance-quicknav
 * Domain Path: /languages/
 * Update URI: https://github.com/deckerweb/breakdance-quicknav
 * GitHub Plugin URI: https://github.com/deckerweb/breakdance-quicknav
 *
 * Copyright © 2025–2026 David Decker – DECKERWEB.
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 * Origin: Breakdance Navigator, Peter Kulcsár, © 2024, GPL v2 or later.
 * https://github.com/beamkiller/breakdance-navigator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DDW_BDQN_VERSION', '2.0.0-beta.1' );
define( 'DDW_BDQN_FILE', __FILE__ );
define( 'DDW_BDQN_DIR', __DIR__ );

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-builder.php';
require_once __DIR__ . '/includes/class-plugin.php';
require_once __DIR__ . '/includes/class-updates.php';
require_once __DIR__ . '/includes/class-activation.php';

// The bootstrap elects a compatible Library before loading its PHP 8 runtime.
require_once __DIR__ . '/includes/deckerweb-plugin-library/bootstrap.php';
deckerweb_library_register_v2( __FILE__, array(), __DIR__ . '/includes/deckerweb-plugin-library' );

add_action( 'plugins_loaded', array( '\Deckerweb\BreakdanceQuickNav\Plugin', 'boot' ) );

// Run after the component's -100 activation handoff, including first activation.
add_action( 'activate_plugin', array( '\Deckerweb\BreakdanceQuickNav\Activation', 'prepare' ), -90, 2 );
