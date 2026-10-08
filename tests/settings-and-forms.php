<?php
/** Targeted regression checks in disposable WordPress installations. */
use Deckerweb\BreakdanceQuickNav\Plugin;
use Deckerweb\BreakdanceQuickNav\Builder;
wp_set_current_user( 1 ); show_admin_bar( true );
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
function quicknav_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
$plugin = new Plugin();
$active = (bool) Builder::active();
$display = $plugin->diagnostics( array() )['breakdance-quicknav']['fields']['display']['value'];
quicknav_check( false === strpos( $display, 'backend=' ) && false !== strpos( $display, ' · ' ), 'display diagnostics use plain status descriptions' );
quicknav_check( false !== strpos( Plugin::url(), ( $active ? 'admin.php' : 'options-general.php' ) . '?page=breakdance-quicknav' ), 'settings URL follows builder state' );
foreach ( array( 'options-general.php', 'breakdance' ) as $parent ) {
 $GLOBALS['parent_file'] = $parent; $GLOBALS['wp_settings_errors'] = array();
 add_settings_error( 'general', 'settings_updated', 'Settings saved.', 'success' );
 ob_start(); if ( 'options-general.php' === $parent ) { require ABSPATH . 'wp-admin/options-head.php'; } $plugin->page(); $html = ob_get_clean();
 quicknav_check( 1 === substr_count( $html, 'Settings saved.' ), 'exactly one save notice for ' . $parent );
}
if ( $active ) {
 $ids = array();
 $content = array();
 foreach ( array( 'publish', 'draft', 'pending', 'future', 'private' ) as $status ) {
  $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => $status, 'post_title' => 'QuickNav status ' . $status, 'post_date' => 'future' === $status ? '2027-01-01 12:00:00' : current_time( 'mysql' ) ) );
  $content[ $status ] = $id;
  \Breakdance\Data\set_meta( $id, '_breakdance_data', array( 'tree_json_string' => '{"root":{"id":1,"children":[{"id":2,"data":{"type":"test"},"children":[]}]}}' ) );
 }
 try {
  $group = new ReflectionMethod( Plugin::class, 'content_group' ); $group->setAccessible( true );
  $settings = \Deckerweb\BreakdanceQuickNav\Settings::defaults(); $settings['limit'] = 100; $settings['drafts'] = true;
  $bar = new WP_Admin_Bar(); $group->invoke( $plugin, $bar, 'check-content', 'root', 'Pages', '#', 'page', 'breakdance', 'content', $settings );
  quicknav_check( (bool) $bar->get_node( 'check-content-published-heading' ) && (bool) $bar->get_node( 'check-content-unpublished-heading' ), 'both status headings rendered' );
  foreach ( $content as $status => $id ) {
   $node = $bar->get_node( 'check-content-' . $id );
   quicknav_check( $node && $node->parent === 'check-content-' . ( 'publish' === $status ? 'published' : 'unpublished' ), 'status grouping for ' . $status );
   if ( 'publish' !== $status ) { quicknav_check( false !== strpos( $node->title, 'bdqn-status-label' ), 'specific status label for ' . $status ); }
  }
  $settings['drafts'] = false; $bar = new WP_Admin_Bar(); $group->invoke( $plugin, $bar, 'check-content', 'root', 'Pages', '#', 'page', 'breakdance', 'content', $settings );
  quicknav_check( ! $bar->get_node( 'check-content-unpublished-heading' ) && ! $bar->get_node( 'check-content-' . $content['draft'] ), 'unpublished group omitted when disabled' );
 } finally { foreach ( $content as $id ) { wp_delete_post( $id, true ); } }

 foreach ( array( 7, 7, 8 ) as $form_id ) {
  $id = wp_insert_post( array( 'post_type' => 'breakdance_form_res', 'post_status' => 'publish', 'post_title' => 'Disposable QuickNav submission' ) ); $ids[] = $id;
  update_post_meta( $id, '_breakdance_uploads', array() );
  update_post_meta( $id, '_breakdance_post_id', 999999 ); update_post_meta( $id, '_breakdance_form_id', $form_id );
  update_post_meta( $id, '_breakdance_form_settings', array( 'form' => array( 'form_name' => 'Contact <test>' ) ) );
 }
 try {
  $method = new ReflectionMethod( Plugin::class, 'form_nodes' ); $method->setAccessible( true );
  $bar = new WP_Admin_Bar(); $method->invoke( $plugin, $bar, 100 );
  $node = $bar->get_node( 'bdqn-form-999999_7' );
  quicknav_check( $node && false !== strpos( $node->href, 'form_id=999999_7' ), 'per-form link uses native combined identifier' );
  quicknav_check( false !== strpos( $node->title, '&lt;test&gt;' ), 'form name escaped' );
  quicknav_check( (bool) $bar->get_node( 'bdqn-form-999999_8' ), 'second form has a separate destination' );
  $small = new WP_Admin_Bar(); $method->invoke( $plugin, $small, 1 ); quicknav_check( 1 === count( $small->get_nodes() ), 'form group respects limit' );
  wp_set_current_user( wp_insert_user( array( 'user_login' => 'form-check-' . wp_generate_password( 8, false ), 'role' => 'subscriber', 'user_pass' => wp_generate_password() ) ) );
  $denied = new WP_Admin_Bar(); $plugin->toolbar( $denied ); quicknav_check( ! $denied->get_node( 'bdqn-form-submissions' ), 'submissions withheld from unauthorized user' );
 } finally { foreach ( $ids as $id ) { wp_delete_post( $id, true ); } wp_set_current_user( 1 ); }
}
echo 'RESULT: settings and forms regression checks passed; BD ' . ( $active ? __BREAKDANCE_VERSION : 'none' ) . "\n";
