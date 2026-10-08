<?php
/** Navigation-only snippet regression in an isolated builder fixture with the host disabled. */
wp_set_current_user( 1 ); show_admin_bar( true );
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
$snippet = getenv( 'BDQN_SNIPPET' );
if ( ! $snippet || ! is_file( $snippet ) ) { throw new RuntimeException( 'Snippet file required.' ); }
require $snippet;
$bar = new WP_Admin_Bar(); do_action( 'admin_bar_menu', $bar );
if ( ! $bar->get_node( 'ddw-breakdance-quicknav' ) || $bar->get_node( 'bdqn-own-settings' ) ) { throw new RuntimeException( 'Snippet navigation/UI scope mismatch.' ); }
$original = json_decode( file_get_contents( __DIR__ . '/original-resources.json' ), true );
foreach ( $original as $group => $items ) { foreach ( $items as $key => $expected ) {
	$node = $bar->get_node( ( 'links' === $group ? 'bdqn-link-' : 'bdqn-about-' ) . $key );
	if ( ! $node || $expected[1] !== $node->href ) { throw new RuntimeException( 'Snippet resource missing.' ); }
} }
if ( defined( 'DDW_BDQN_VERSION' ) || isset( $GLOBALS['deckerweb_library_candidates_v1'] ) ) { throw new RuntimeException( 'Snippet unexpectedly booted host/components.' ); }
echo "RESULT: 17 snippet checks (navigation, UI scope and all resource destinations)\n";
