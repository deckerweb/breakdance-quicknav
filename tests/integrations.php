<?php
/** Addon regression suite; run in a disposable fixture containing the audited packages. */
use Deckerweb\BreakdanceQuickNav\Integrations;
use Deckerweb\BreakdanceQuickNav\Settings;
use Deckerweb\BreakdanceQuickNav\Plugin;
wp_set_current_user( 1 ); show_admin_bar( true );
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
global $checks; $checks = 0;
function integration_assert( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( 'FAIL: ' . $label ); } $checks++; echo 'PASS: ' . $label . "\n"; }
$original = get_option( Settings::OPTION );
try {
 $settings = Settings::defaults(); update_option( Settings::OPTION, $settings );
 $rows = Integrations::rows();
 foreach ( array( 'sitecare' => 'sitecare-breakdance-hide-topbar', 'yabe-webfont' => 'jooosi_fon', 'elements-hive' => 'elements_hive', 'express' => 'vxn_express_setup', 'breakcolorui' => 'breakcolorui-sync', 'wpsix-exporter' => 'wpsix-exporter' ) as $id => $slug ) {
  integration_assert( isset( $rows[$id] ) && 'available' === $rows[$id]['status'], $id . ' runtime destination available' );
  integration_assert( false !== strpos( $rows[$id]['links'][0]['url'], 'admin.php?page=' . $slug ), $id . ' exact source-verified URL' );
 }
 integration_assert( 1 === count( $rows['elements-hive']['children'] ) && false !== strpos( $rows['elements-hive']['children'][0]['url'], 'elements_hive_cloudflare_turnstile' ), 'Elements Hive submenu verified' );
 integration_assert( 'unavailable' === $rows['wpml']['status'] && ! $rows['wpml']['links'], 'WPML integration waits for actual WPML dependencies' );
 foreach ( array( 'wpsix-elements', 'image-replace' ) as $id ) { integration_assert( 'no-menu' === $rows[$id]['status'] && ! $rows[$id]['links'], $id . ' remains diagnostic without invented menu' ); }
 foreach ( array( 'breakmade', 'dancepad', 'smithy', 'phox', 'builder-languages' ) as $id ) { integration_assert( 'pending' === $rows[$id]['status'] && ! $rows[$id]['links'], $id . ' pending package produces no link' ); }
 $bad = Settings::sanitize( array_merge( $settings, array( 'integrations' => array( 'sitecare' => '0', 'express' => array(), 'bad id' => true, 'unknown' => 'yes' ) ) ) );
 integration_assert( array( 'sitecare' => false ) === $bad['integrations'], 'malformed integration preferences rejected' );
 integration_assert( Integrations::enabled( 'sitecare', $settings ), 'existing sites default to enabled integrations' );
 $http_calls = 0; $http_guard = static function( $pre ) use ( &$http_calls ) { $http_calls++; return $pre; }; add_filter( 'pre_http_request', $http_guard );
 $plugin = new Plugin(); $bar = new WP_Admin_Bar(); $plugin->toolbar( $bar );
 integration_assert( 0 === $http_calls, 'addon toolbar discovery performs no HTTP requests' ); remove_filter( 'pre_http_request', $http_guard );
 integration_assert( (bool) $bar->get_node( 'bdqn-sitecare' ) && (bool) $bar->get_node( 'bdqn-yabe-webfont' ), 'new and renamed addons in toolbar' );
 integration_assert( (bool) $bar->get_node( 'bdqn-settings-ai' ) && ! $bar->get_node( 'bdqn-ai' ), 'AI remains one settings tab without duplicate addon entry' );
 $children = array_filter( (array) $bar->get_nodes(), static function ( $node ) { return 'bdqn-elements-hive' === $node->parent; } ); integration_assert( 1 === count( $children ), 'addon child appears once' );
 $settings['integrations'] = array( 'sitecare' => false, 'ai' => false ); $settings['integration_children'] = false; update_option( Settings::OPTION, $settings );
 $bar = new WP_Admin_Bar(); $plugin->toolbar( $bar ); integration_assert( ! $bar->get_node( 'bdqn-sitecare' ) && ! $bar->get_node( 'bdqn-settings-ai' ), 'website switches hide main and tab integrations' );
 $children = array_filter( (array) $bar->get_nodes(), static function ( $node ) { return 'bdqn-elements-hive' === $node->parent; } ); integration_assert( ! $children, 'optional submenu switch hides child links' );
 $old_form = $settings; unset( $old_form['integrations'], $old_form['integration_children'] ); $saved = $plugin->save_settings( $old_form ); integration_assert( false === $saved['integrations']['sitecare'] && ! $saved['integration_children'], 'older form preserves new saved preferences' );
 $target = array( 'slug' => 'sitecare-breakdance-hide-topbar', 'file' => 'admin.php', 'capability' => 'manage_options', 'ready' => true );
 foreach ( array( array( 'slug' => 'https://external.test' ), array( 'file' => 'https://external.test' ), array( 'ready' => false ), array( 'ready' => 'yes' ), array( 'slug' => array() ) ) as $bad ) { integration_assert( '' === Integrations::destination( array_merge( $target, $bad ) ), 'invalid/unavailable destination rejected' ); }
 if ( is_admin() ) { global $submenu; $menu_backup = $submenu; foreach ( $submenu as &$items ) { foreach ( $items as &$item ) { if ( ( $item[2] ?? '' ) === $target['slug'] ) { $item[1] = 'not_a_real_capability'; } } unset( $item ); } unset( $items ); integration_assert( '' === Integrations::destination( $target ), 'registered menu capability remains authoritative' ); $submenu = $menu_backup;
 integration_assert( '' === Integrations::destination( array_merge( $target, array( 'slug' => 'missing_verified_menu' ) ) ), 'missing new admin menu is omitted' ); }
 $custom = static function( $defs ) { $defs['custom'] = array( 'label' => 'Custom alias', 'loaded' => true, 'targets' => array( array( 'slug' => 'sitecare-breakdance-hide-topbar', 'file' => 'admin.php', 'capability' => 'manage_options', 'ready' => true ) ) ); $defs['broken'] = 'invalid'; return $defs; };
 add_filter( 'ddw/quicknav/bd_integrations', $custom ); update_option( Settings::OPTION, Settings::defaults() );
 $bar = new WP_Admin_Bar(); $plugin->toolbar( $bar ); $urls = array(); foreach ( (array) $bar->get_nodes() as $node ) { if ( false !== strpos( $node->href, 'page=sitecare-breakdance-hide-topbar' ) ) { $urls[] = $node->href; } }
 integration_assert( 1 === count( $urls ), 'custom integration filter deduplicates identical destinations' ); remove_filter( 'ddw/quicknav/bd_integrations', $custom );
 $subscriber = wp_insert_user( array( 'user_login' => 'addon-check-' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
 wp_set_current_user( $subscriber ); integration_assert( '' === Integrations::destination( $target ), 'unauthorized destination withheld' );
 $bar = new WP_Admin_Bar(); $plugin->toolbar( $bar ); integration_assert( ! $bar->get_node( 'bdqn-sitecare' ), 'unauthorized toolbar withheld' ); wp_set_current_user( 1 );
 $GLOBALS['parent_file'] = 'breakdance'; ob_start(); $plugin->page(); $html = ob_get_clean();
 integration_assert( false !== strpos( $html, 'ddw_bdqn_settings[integrations][sitecare]' ) && false !== strpos( $html, 'bdqn-integrations' ), 'settings controls use existing native option form' );
 foreach ( array( 'de_DE', 'de_DE_formal' ) as $locale ) { unload_textdomain( 'breakdance-quicknav', true ); load_textdomain( 'breakdance-quicknav', DDW_BDQN_DIR . '/languages/breakdance-quicknav-' . $locale . '.mo', $locale ); integration_assert( 'Drittanbieter-Integrationen' === __( 'Third-party integrations', 'breakdance-quicknav' ), $locale . ' integration translation' ); }
} finally { update_option( Settings::OPTION, $original ); wp_set_current_user( 1 ); }
echo 'RESULT: ' . $checks . ' addon assertions; BD ' . __BREAKDANCE_VERSION . '; context ' . ( is_admin() ? 'admin' : 'frontend' ) . "\n";
