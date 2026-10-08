<?php
/** Real Elements Hive Pro checks plus explicitly simulated source-based adapters. */
namespace Dancepad { class Initialize {} } // Test fixture only; no vendor licensing code loaded.
namespace {
use Deckerweb\BreakdanceQuickNav\Integrations;
use Deckerweb\BreakdanceQuickNav\Settings;
use Deckerweb\BreakdanceQuickNav\Plugin;
wp_set_current_user( 1 ); show_admin_bar( true );
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
global $checks; $checks = 0;
function premium_assert( $ok, $label ) { global $checks; if ( ! $ok ) { throw new \RuntimeException( 'FAIL: ' . $label ); } $checks++; echo 'PASS: ' . $label . "\n"; }
$original = get_option( Settings::OPTION );
try {
 $rows = Integrations::rows();
 premium_assert( 'available' === $rows['elements-hive-pro']['status'] && '1.7.0' === $rows['elements-hive-pro']['version'], 'real Elements Hive Pro loaded with exact version' );
 premium_assert( 2 === count( $rows['elements-hive-pro']['children'] ), 'real Elements Hive Pro License and Tools destinations' );
 // These definitions only simulate known source menu contracts; no runtime certification.
 function destiny_elements_menu() {} function destiny_elements() {}
 define( 'DANCEPAD_VERSION', '2.1.0' );
 add_menu_page( 'Destiny Elements', 'Destiny Elements', 'manage_options', 'destiny-elements.php', 'destiny_elements' );
 add_submenu_page( 'breakdance', 'Dancepad', 'Dancepad', 'manage_options', 'dancepad', '__return_null' );
 $rows = Integrations::rows();
 premium_assert( false !== strpos( $rows['destiny-elements']['links'][0]['url'], 'page=destiny-elements.php' ), 'simulated Destiny literal dot-php page slug' );
 premium_assert( false !== strpos( $rows['dancepad']['links'][0]['url'], 'page=dancepad' ), 'simulated Dancepad native admin page' );
 $base = array( 'file' => 'admin.php', 'capability' => 'manage_options', 'ready' => true );
 foreach ( array( '../destiny-elements.php', 'destiny-elements.php.php', 'evil.php?x=1', 'evil.php/x' ) as $slug ) { premium_assert( '' === Integrations::destination( array_merge( $base, array( 'slug' => $slug ) ) ), 'unsafe dot-php destination rejected' ); }
 $settings = Settings::defaults(); update_option( Settings::OPTION, $settings ); $plugin = new Plugin();
 $bar = new \WP_Admin_Bar(); $plugin->toolbar( $bar ); premium_assert( 'bdqn-addons' === $bar->get_node( 'bdqn-elements-hive-pro' )->parent, 'Pro collected by default' );
 $settings['integration_direct']['elements-hive-pro'] = true; update_option( Settings::OPTION, $settings );
 $bar = new \WP_Admin_Bar(); $plugin->toolbar( $bar ); premium_assert( 'ddw-breakdance-quicknav' === $bar->get_node( 'bdqn-elements-hive-pro' )->parent, 'Pro promoted to main menu' );
 $children = array_filter( (array) $bar->get_nodes(), static function( $n ) { return 'bdqn-elements-hive-pro' === $n->parent; } ); premium_assert( 2 === count( $children ), 'promotion preserves Pro children' );
 foreach ( $rows as $id => $row ) { $settings['integrations'][$id] = false; } update_option( Settings::OPTION, $settings );
 $bar = new \WP_Admin_Bar(); $plugin->toolbar( $bar ); premium_assert( ! $bar->get_node( 'bdqn-addons' ), 'empty addon collection omitted' );
 $old = $settings; unset( $old['integration_direct'] ); premium_assert( true === $plugin->save_settings( $old )['integration_direct']['elements-hive-pro'], 'older form preserves direct addon preference' );
 ob_start(); $plugin->page(); $html = ob_get_clean();
 premium_assert( 4 === substr_count( $html, 'class="bdqn-panel"' ) && 1 === substr_count( $html, 'id="bdqn-settings-form"' ), 'four sections in one secure native form' );
 premium_assert( false !== strpos( $html, 'class="bdqn-savebar"' ) && false !== strpos( $html, '<details' ), 'save bar and expandable missing-addon groups rendered' );
} finally { update_option( Settings::OPTION, $original ); }
echo 'RESULT: ' . $checks . " Pro/runtime and source-contract checks\n";
}
