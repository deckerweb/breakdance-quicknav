<?php
/** Real native WordPress replacement of a locally installed 1.1.0 in an isolated fixture. */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
$package = getenv( 'BDQN_UPGRADE_ZIP' );
if ( ! $package || ! is_file( $package ) ) { throw new RuntimeException( 'Local upgrade package required.' ); }
$plugin = 'breakdance-quicknav/breakdance-quicknav.php';
update_option( 'ddw_bdqn_settings', array( 'name' => 'Retained', 'limit' => 13 ) );
update_user_option( 1, 'ddw_bdqn_preferences', array( 'limit' => 9 ), false );
update_option( 'breakdance_foreign_test', 'keep' );
set_site_transient( 'update_plugins', (object) array( 'checked' => array( $plugin => '1.1.0' ), 'response' => array( $plugin => (object) array( 'slug' => 'breakdance-quicknav', 'plugin' => $plugin, 'new_version' => '2.0.0-beta.3', 'package' => $package ) ) ) );
$upgrader = new Plugin_Upgrader( new WP_Upgrader_Skin() );
$result = $upgrader->upgrade( $plugin );
if ( true !== $result ) { throw new RuntimeException( 'Native legacy upgrade failed.' ); }
wp_clean_plugins_cache( false ); $info = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
if ( '2.0.0-beta.3' !== $info['Version'] || 'Retained' !== get_option( 'ddw_bdqn_settings' )['name'] || 9 !== get_user_option( 'ddw_bdqn_preferences', 1 )['limit'] || 'keep' !== get_option( 'breakdance_foreign_test' ) ) { throw new RuntimeException( 'Update data/version mismatch.' ); }
echo "RESULT: 4 native legacy upgrade checks (version, settings, personal preferences, foreign data)\n";
