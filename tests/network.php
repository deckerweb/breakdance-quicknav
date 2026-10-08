<?php
/** Isolated network settings/lifecycle regressions; no production data. */
use Deckerweb\BreakdanceQuickNav\Settings;
use Deckerweb\BreakdanceQuickNav\Plugin;
if ( ! is_multisite() ) { throw new RuntimeException( 'Network fixture required.' ); }
global $checks; $checks = 0;
/** @param bool $ok Test result. @param string $label Description. @return void */
function bdqn_network_assert( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( 'FAIL: ' . $label ); } $checks++; echo 'PASS: ' . $label . "\n"; }
wp_set_current_user( 1 );
$main = get_current_blog_id();
$second = wp_insert_site( array( 'domain' => 'bdqn.test', 'path' => '/second-' . time() . '/', 'title' => 'Second test site', 'user_id' => 1 ) );
if ( is_wp_error( $second ) ) { throw new RuntimeException( $second->get_error_message() ); }
update_option( Settings::OPTION, array_merge( Settings::defaults(), array( 'name' => 'First', 'delete_data' => false ) ) );
update_user_option( 1, Settings::USER_OPTION, array( 'limit' => 5 ), false );
update_option( 'breakdance_foreign_test', 'keep' );
switch_to_blog( $second );
update_option( Settings::OPTION, array_merge( Settings::defaults(), array( 'name' => 'Second', 'delete_data' => true ) ) );
update_user_option( 1, Settings::USER_OPTION, array( 'limit' => 9 ), false );
update_option( 'breakdance_foreign_test', 'keep' );
bdqn_network_assert( 'Second' === Settings::get()['name'] && 9 === Settings::get()['limit'], 'second site options and preferences isolated' );
restore_current_blog();
bdqn_network_assert( 'First' === Settings::get()['name'] && 5 === Settings::get()['limit'], 'main site options and preferences unchanged' );
// Execute the real uninstall entry in an isolated fixture, without removing plugin files.
define( 'WP_UNINSTALL_PLUGIN', plugin_basename( DDW_BDQN_FILE ) );
require DDW_BDQN_DIR . '/uninstall.php';
bdqn_network_assert( get_current_blog_id() === $main, 'blog context restored after network cleanup' );
bdqn_network_assert( 'First' === get_option( Settings::OPTION )['name'] && 5 === get_user_option( Settings::USER_OPTION, 1 )['limit'], 'opt-out site retains host settings and personal preferences' );
bdqn_network_assert( 'keep' === get_option( 'breakdance_foreign_test' ), 'main site Oxygen data untouched' );
switch_to_blog( $second );
bdqn_network_assert( false === get_option( Settings::OPTION ), 'opt-in site removes host settings' );
bdqn_network_assert( false === get_user_option( Settings::USER_OPTION, 1 ), 'opt-in site removes site-scoped personal preferences' );
bdqn_network_assert( 'keep' === get_option( 'breakdance_foreign_test' ), 'second site Oxygen data untouched' );
restore_current_blog();
echo 'RESULT: ' . $checks . " network checks\n";
