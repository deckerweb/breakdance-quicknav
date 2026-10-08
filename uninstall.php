<?php
/** Scoped uninstall; Breakdance and other plugins' data always remain. @package BreakdanceQuickNav */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

delete_site_transient( 'ddw_ghru_' . substr( md5( 'https://github.com/deckerweb/breakdance-quicknav' ), 0, 24 ) );

require_once __DIR__ . '/includes/deckerweb-plugin-library/lifecycle.php';
deckerweb_library_uninstall_v3( __DIR__ . '/breakdance-quicknav.php' );

/**
 * Remove one website's host settings only when that website opted in.
 * @return void Deletes QuickNav preferences; never Breakdance content or shared user accounts.
 */
function ddw_bdqn_uninstall_site() {
	$settings = get_option( 'ddw_bdqn_settings', array() );
	if ( is_array( $settings ) && ! empty( $settings['delete_data'] ) ) {
		delete_option( 'ddw_bdqn_settings' );
		global $wpdb;
		$meta_key = $wpdb->get_blog_prefix( get_current_blog_id() ) . 'ddw_bdqn_preferences';
		delete_metadata( 'user', 0, $meta_key, '', true );
	}
}

if ( is_multisite() ) {
	$offset = 0;
	do {
		$sites = get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => $offset ) );
		foreach ( $sites as $site ) { switch_to_blog( $site ); ddw_bdqn_uninstall_site(); restore_current_blog(); }
		$offset += 100;
	} while ( count( $sites ) === 100 );
} else { ddw_bdqn_uninstall_site(); }
