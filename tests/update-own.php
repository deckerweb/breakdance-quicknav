<?php
/** Run native single/bulk updates of this host only in an isolated fixture. */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
$package = getenv( 'BDQN_UPGRADE_ZIP' );
if ( ! $package || ! is_file( $package ) ) { throw new RuntimeException( 'Local package required.' ); }
$plugin = plugin_basename( DDW_BDQN_FILE );
update_option( 'ddw_bdqn_settings', array( 'name' => 'Retained', 'limit' => 13 ) );
update_user_option( 1, 'ddw_bdqn_preferences', array( 'limit' => 9 ), false );
$offer = (object) array( 'slug' => 'breakdance-quicknav', 'plugin' => $plugin, 'new_version' => '2.0.0', 'package' => $package );
set_site_transient( 'update_plugins', (object) array( 'checked' => array( $plugin => DDW_BDQN_VERSION ), 'response' => array( $plugin => $offer ) ) );
$upgrader = new Plugin_Upgrader( new WP_Upgrader_Skin() );
$result = 'bulk' === getenv( 'BDQN_UPDATE_MODE' ) ? $upgrader->bulk_upgrade( array( $plugin ) ) : $upgrader->upgrade( $plugin );
$ok = is_array( $result ) ? ( $result[$plugin] ?? false ) : $result;
if ( true !== $ok && ! ( is_array( $ok ) && 'breakdance-quicknav' === ( $ok['destination_name'] ?? '' ) ) ) { throw new RuntimeException( 'Native host update failed.' ); }
wp_clean_plugins_cache( false ); $info = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
if ( '2.0.0' !== $info['Version'] || 'Retained' !== get_option( 'ddw_bdqn_settings' )['name'] || 9 !== get_user_option( 'ddw_bdqn_preferences', 1 )['limit'] ) { throw new RuntimeException( 'Native update preservation failed.' ); }
echo 'RESULT: 3 native ' . ( getenv( 'BDQN_UPDATE_MODE' ) ?: 'single' ) . " update checks\n";
