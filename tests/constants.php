<?php
/** Legacy immutable configuration and target isolation tests. */
use Deckerweb\BreakdanceQuickNav\Settings;
use Deckerweb\BreakdanceQuickNav\Plugin;
define( 'BDQN_ICON', 'yellow' );
define( 'BDQN_NAME_IN_ADMINBAR', 'Configured' );
define( 'BDQN_NUMBER_TEMPLATES', 7 );
define( 'BDQN_VIEW_CAPABILITY', 'edit_posts' );
define( 'BDQN_ENABLED_USERS', array() );
define( 'BDQN_DISABLE_FOOTER', 'yes' );
wp_set_current_user( 1 );
$values = Settings::get();
if ( 'yellow' !== $values['icon'] || 'Configured' !== $values['name'] || 7 !== $values['limit'] || 'edit_posts' !== $values['capability'] || array( -1 ) !== $values['users'] || $values['footer'] ) { throw new RuntimeException( 'Legacy precedence failed.' ); }
echo "PASS: legacy constants take final precedence\n";
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
show_admin_bar( true ); $bar = new WP_Admin_Bar(); ( new Plugin() )->toolbar( $bar );
if ( $bar->get_node( 'ddw-breakdance-quicknav' ) ) { throw new RuntimeException( 'Empty configured user allowlist allowed a user.' ); }
update_option( Settings::OPTION, array_merge( Settings::defaults(), array( 'name' => 'Stored', 'limit' => 12 ) ) );
$submitted = ( new Plugin() )->save_settings( array( 'name' => 'Changed', 'limit' => 99 ) );
if ( 'Stored' !== $submitted['name'] || 12 !== $submitted['limit'] ) { throw new RuntimeException( 'Locked saved values overwritten.' ); }
echo "PASS: locked saved values retained\n";
echo "PASS: empty legacy allowlist denies every user\nRESULT: 3 constants checks\n";
