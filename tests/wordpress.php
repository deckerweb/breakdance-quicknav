<?php
/** Run with WP-CLI eval-file in an isolated installation; creates disposable fixtures. */
use Deckerweb\BreakdanceQuickNav\Builder;
use Deckerweb\BreakdanceQuickNav\Settings;
use Deckerweb\BreakdanceQuickNav\Plugin;
global $checks; $checks = 0;
/** @param bool $ok Expected condition. @param string $label Assertion description. @return void */
function bdqn_assert( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( 'FAIL: ' . $label ); } $checks++; echo 'PASS: ' . $label . "\n"; }
wp_set_current_user( 1 ); show_admin_bar( true );
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
$plugin = new Plugin(); $settings = Settings::defaults(); update_option( Settings::OPTION, $settings ); update_user_option( 1, Settings::USER_OPTION, array(), false );
$active = (bool) Builder::active();
$bar = new WP_Admin_Bar(); $plugin->toolbar( $bar );
bdqn_assert( $active === (bool) $bar->get_node( 'ddw-breakdance-quicknav' ), 'safe toolbar with/without Breakdance' );
bdqn_assert( ! has_action( 'enqueue_block_editor_assets', array( $plugin, 'adminbar_block_editor_fullscreen' ) ), 'no editor toolbar override' );
$bad = Settings::sanitize( array( 'limit' => array(), 'name' => array(), 'capability' => array(), 'post_types' => array( 'page', 'invalid' ), 'users' => '1,2;2 -3 junk' ) );
bdqn_assert( 20 === $bad['limit'] && 'BD' === $bad['name'] && 'activate_plugins' === $bad['capability'], 'malformed scalar settings safe' );
bdqn_assert( array( 'page' ) === $bad['post_types'] && array( 1, 2, 3 ) === $bad['users'], 'post types and users normalized' );
bdqn_assert( 100 === Settings::sanitize( array( 'limit' => 9999 ) )['limit'], 'bounded list length' );
bdqn_assert( array() === Settings::sanitize( array( 'groups' => array( '' ) ) )['groups'], 'all groups can be disabled' );
$settings['users'] = array( 999999 ); update_option( Settings::OPTION, $settings ); $bar = new WP_Admin_Bar(); $plugin->toolbar( $bar );
bdqn_assert( ! $bar->get_node( 'ddw-breakdance-quicknav' ), 'site allowlist enforced' );
$settings = Settings::defaults(); update_option( Settings::OPTION, $settings );
update_user_option( 1, Settings::USER_OPTION, array( 'frontend' => 'hide', 'limit' => 7 ), false );
bdqn_assert( ! Settings::get()['frontend'] && 7 === Settings::get()['limit'], 'personal site-scoped preference' );
$settings['backend'] = false; update_option( Settings::OPTION, $settings ); update_user_option( 1, Settings::USER_OPTION, array( 'backend' => 'inherit' ), false );
bdqn_assert( ! Settings::get()['backend'], 'personal preference cannot re-enable site-disabled display' );
update_option( Settings::OPTION, Settings::defaults() ); update_user_option( 1, Settings::USER_OPTION, array(), false );
$_POST = array( 'bdqn_preferences' => array( 'limit' => 88 ) ); $plugin->save_profile( 1 );
bdqn_assert( array() === get_user_option( Settings::USER_OPTION ), 'profile save rejects missing nonce' );
$_POST['bdqn_profile_nonce'] = wp_create_nonce( 'bdqn-profile-1' ); $plugin->save_profile( 1 );
bdqn_assert( 88 === get_user_option( Settings::USER_OPTION )['limit'], 'authorized profile save' );
update_user_option( 1, Settings::USER_OPTION, array(), false );
$subscriber = wp_insert_user( array( 'user_login' => 'bdqn-subscriber-' . wp_generate_password( 8, false ), 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
wp_set_current_user( $subscriber ); $_POST['bdqn_profile_nonce'] = wp_create_nonce( 'bdqn-profile-1' ); $plugin->save_profile( 1 );
bdqn_assert( array() === get_user_option( Settings::USER_OPTION, 1 ), 'cannot change another user profile' );
$bar = new WP_Admin_Bar(); $plugin->toolbar( $bar ); bdqn_assert( ! $bar->get_node( 'ddw-breakdance-quicknav' ), 'subscriber toolbar denied' );
wp_set_current_user( 1 );
$links = $plugin->action_links( array( 'deactivate' => '<a>Deactivate</a>' ) );
bdqn_assert( false !== strpos( reset( $links ), ( $active ? 'admin.php' : 'options-general.php' ) . '?page=breakdance-quicknav' ), 'settings link precedes deactivate' );
bdqn_assert( false === strpos( implode( ' ', $plugin->row_meta( array(), plugin_basename( DDW_BDQN_FILE ) ) ), 'MERGE0' ), 'newsletter contains no personal parameters' );
bdqn_assert( ! isset( $plugin->diagnostics( array() )['breakdance-quicknav']['fields']['BDQN_ENABLED_USERS'] ), 'diagnostics exclude user IDs' );
if ( $active ) {
	$ids = array();
	foreach ( array( 'publish', 'draft' ) as $status ) {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'QuickNav ' . $status, 'post_status' => $status ) );
		\Breakdance\Data\set_meta( $id, '_breakdance_data', array( 'tree_json_string' => '{"root":{"id":1,"children":[{"id":2,"data":{"type":"test"},"children":[]}]}}' ) ); $ids[$status] = $id;
	}
	$empty = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Empty shell', 'post_status' => 'publish' ) ); update_post_meta( $empty, '_breakdance_data', array( 'tree_json_string' => '{"root":{"children":[]}}' ) );
	$found = wp_list_pluck( Builder::posts( 'page', 'breakdance', 'content', $settings ), 'ID' );
	bdqn_assert( in_array( $ids['publish'], $found, true ) && ! in_array( $ids['draft'], $found, true ) && ! in_array( $empty, $found, true ), 'published authored pages only by default' );
	$settings['drafts'] = true; bdqn_assert( in_array( $ids['draft'], wp_list_pluck( Builder::posts( 'page', 'breakdance', 'content', $settings ), 'ID' ), true ), 'drafts opt-in' );
	$old_site = get_option( 'siteurl' ); $old_home = get_option( 'home' ); update_option( 'siteurl', 'http://example.test/wordpress' ); update_option( 'home', 'http://example.test' );
	$url = Builder::edit_url( $ids['publish'], 'breakdance' ); bdqn_assert( false === strpos( $url, '/wordpress' ) && false !== strpos( $url, 'breakdance=builder' ), 'official home-based builder URL' );
	bdqn_assert( false !== strpos( Builder::styles_url(), 'mode=browse' ) && false !== strpos( Builder::styles_url(), 'returnUrl=' ), 'official global styles browse URL' );
	update_option( 'siteurl', $old_site ); update_option( 'home', $old_home );
	/** @param array $args Query arguments. @return array Excluded query. */
	$filter = static function ( $args ) { $args['post__in'] = array( -1 ); return $args; }; add_filter( 'ddw/quicknav/bd_get_template_type', $filter );
	bdqn_assert( ! Builder::posts( 'page', 'breakdance', 'content', $settings ), 'query filter return effective' ); remove_filter( 'ddw/quicknav/bd_get_template_type', $filter );
	/** @param array $tabs Registered tabs. @return array Customized tabs. */
	$filter = static function ( $tabs ) { return array( 'tools' => 'Filtered tools' ); }; add_filter( 'ddw/quicknav/bd_settings', $filter );
	bdqn_assert( array( 'tools' => 'Filtered tools' ) === Builder::tabs( 'breakdance' ), 'settings filter return effective' ); remove_filter( 'ddw/quicknav/bd_settings', $filter );
	$tabs = Builder::tabs( 'breakdance' ); bdqn_assert( isset( $tabs['bloat_eliminator'], $tabs['header_footer'], $tabs['elements'] ), 'registered settings tabs retained' );
	bdqn_assert( 'license' === array_key_last( $tabs ), 'license remains final tab' );
	bdqn_assert( ( 0 === strpos( __BREAKDANCE_VERSION, '3.' ) ) === isset( $tabs['agents-and-mcp'] ), 'Agents & MCP only when registered in Breakdance 3' );
	$id = wp_insert_post( array( 'post_type' => 'breakdance_template', 'post_title' => 'Renamed internal', 'post_status' => 'publish' ) ); update_post_meta( $id, '_breakdance_template_settings', '{"fallback":true}' );
	bdqn_assert( ! in_array( $id, wp_list_pluck( Builder::posts( 'breakdance_template', 'breakdance', 'templates', $settings ), 'ID' ), true ), 'fallback excluded by metadata' );
	$id2 = wp_insert_post( array( 'post_type' => 'breakdance_template', 'post_title' => 'Fallback: legitimate', 'post_status' => 'publish' ) ); update_post_meta( $id2, '_breakdance_template_settings', '{"fallback":false}' );
	bdqn_assert( in_array( $id2, wp_list_pluck( Builder::posts( 'breakdance_template', 'breakdance', 'templates', $settings ), 'ID' ), true ), 'legitimate fallback-prefixed title preserved' );
	$bar = new WP_Admin_Bar(); $plugin->toolbar( $bar ); $original = json_decode( file_get_contents( __DIR__ . '/original-resources.json' ), true );
	foreach ( $original as $group => $items ) { foreach ( $items as $key => $info ) { $node = $bar->get_node( ( 'links' === $group ? 'bdqn-link-' : 'bdqn-about-' ) . $key ); bdqn_assert( $node && $node->href === $info[1], 'preserved resource: ' . $key ); } }
	bdqn_assert( (bool) $bar->get_node( 'bdqn-global-blocks' ) && (bool) $bar->get_node( 'bdqn-popups' ), 'global blocks and popups retained' );
	if ( defined( 'BREAKDANCE_AI_VERSION' ) ) { bdqn_assert( isset( $tabs['ai'] ), 'actual AI tab retained' ); }
	if ( defined( 'WPSIX_EXPORTER_VERSION' ) ) { bdqn_assert( false !== strpos( $bar->get_node( 'bdqn-wpsix-exporter' )->href, 'page=wpsix-exporter' ), 'actual Exporter 2.0.5 menu slug' ); }
	if ( defined( 'WPSIX_ELEMENTS_VERSION' ) && defined( 'IMAGE_REPLACE_VERSION' ) ) { bdqn_assert( isset( Builder::addons()['WPSix Elements'], Builder::addons()['Image Replacer'] ), 'menu-less addons diagnosed' ); }
	wp_set_current_user( $subscriber ); bdqn_assert( '' === Builder::edit_url( $ids['publish'], 'breakdance' ), 'unauthorized builder URL withheld' ); wp_set_current_user( 1 );
}
foreach ( array( 'de_DE' => 'Deine Direktwege. Breakdance 2 & 3.', 'de_DE_formal' => 'Ihre Direktwege. Breakdance 2 & 3.' ) as $locale => $expected ) {
	unload_textdomain( 'breakdance-quicknav', true ); load_textdomain( 'breakdance-quicknav', DDW_BDQN_DIR . '/languages/breakdance-quicknav-' . $locale . '.mo', $locale );
	bdqn_assert( $expected === __( 'Your shortcuts. Breakdance 2 & 3.', 'breakdance-quicknav' ), $locale . ' host translation' );
}
$history = require DDW_BDQN_DIR . '/includes/plugin-history.php'; bdqn_assert( false !== strpos( $history( true ), 'Neu:' ) && false === strpos( $history( true ), '<pre>' ), 'escaped structured German history' );
ob_start(); $plugin->page(); $html = ob_get_clean(); bdqn_assert( false !== strpos( $html, 'action="options.php"' ) && false !== strpos( $html, '<dialog' ), 'native settings form and history dialog' );
bdqn_assert( false !== strpos( $html, '</header><hr class="wp-header-end">' ) && false === strpos( $html, 'bdqn-footer-icon' ), 'native notice anchor follows the complete header; footer has no icon' );
bdqn_assert( false === strpos( $html, 'Classic' ) && false === strpos( $html, 'is-fullscreen-mode' ), 'reference/editor artifacts removed' );
if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) { bdqn_assert( '2.1.0' === \Deckerweb\GitHubReleaseUpdater\V2\Updater::IMPLEMENTATION_VERSION, 'updater 2.1.0 registered' ); }
else { bdqn_assert( \Deckerweb\BreakdanceQuickNav\Updates::$error && ! class_exists( '\\Deckerweb\\GitHubReleaseUpdater\\V2\\Updater' ), 'updater safely paused on PHP 7.4' ); }
if ( version_compare( PHP_VERSION, '8.0', '>=' ) ) { $library = $GLOBALS['deckerweb_library_runtime_v1']; bdqn_assert( '0.8.1' === get_class( $library )::VERSION, 'Library 0.8.1 elected' ); }
else { bdqn_assert( empty( $GLOBALS['deckerweb_library_runtime_v1'] ), 'Library safely paused on PHP 7.4' ); }
echo 'RESULT: ' . $checks . ' assertions; WP ' . $GLOBALS['wp_version'] . '; PHP ' . PHP_VERSION . '; BD ' . ( $active ? __BREAKDANCE_VERSION : 'none' ) . "\n";
