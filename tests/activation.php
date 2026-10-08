<?php
/** Scoped host activation and unchanged foreign dependency protection. */
wp_set_current_user( 1 );
$checks = 0;
/** @param bool $ok Expected outcome. @param string $label Assertion label. @return void */
function bdqn_activation_assert( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo 'PASS: ' . $label . "\n"; }
do_action( 'activate_plugin', plugin_basename( DDW_BDQN_FILE ), is_multisite() );
bdqn_activation_assert( true, 'own host activation allowed without builder' );
/** @return callable Test-only die handler. */
$handler = static function () {
	/** @param mixed $message Error message. @param mixed $title Dialog title. @param mixed $args Error arguments. @return void */
	return static function ( $message, $title = '', $args = array() ) { throw new RuntimeException( wp_strip_all_tags( (string) $message ) ); };
};
add_filter( 'wp_die_handler', $handler, PHP_INT_MAX ); $blocked = false;
try { do_action( 'activate_plugin', 'advanced-scripts-quicknav/advanced-scripts-quicknav.php', false ); }
catch ( RuntimeException $e ) { $blocked = false !== stripos( $e->getMessage(), 'Advanced Scripts' ); }
finally { remove_filter( 'wp_die_handler', $handler, PHP_INT_MAX ); }
bdqn_activation_assert( $blocked, 'foreign host dependency protection retained' );
$runtime = $GLOBALS['deckerweb_library_runtime_v1'];
bdqn_activation_assert( '0.8.1' === get_class( $runtime )::VERSION, 'latest compatible Library wins with older host installed' );
echo "RESULT: 3 activation checks\n";
