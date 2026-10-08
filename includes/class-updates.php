<?php
/**
 * Host integration. @package BreakdanceQuickNav
 * Adapted from Oxygen QuickNav 2.0.0, © 2025–2026 David Decker – DECKERWEB.
 * GPL-2.0-or-later. https://github.com/deckerweb/oxygen-quicknav/tree/v2.0.0
 */
namespace Deckerweb\BreakdanceQuickNav;

defined( 'ABSPATH' ) || exit;

/** Owns the GitHub updater and rejects mismatched or incomplete host packages. */
final class Updates {
	/** @var bool Whether a component could not register safely. */
	public static $error = false;

	/** @return void Registers pinned update hooks after host translations load. */
	public static function register() {
		static $registered = false;
		if ( $registered ) { return; }
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) { self::$error = true; return; }
		if ( ! class_exists( '\\Deckerweb\\GitHubReleaseUpdater\\V2\\Updater' ) ) { require_once DDW_BDQN_DIR . '/includes/deckerweb-github-release-updater-v2.php'; }
		if ( ! defined( '\\Deckerweb\\GitHubReleaseUpdater\\V2\\Updater::IMPLEMENTATION_VERSION' ) || version_compare( \Deckerweb\GitHubReleaseUpdater\V2\Updater::IMPLEMENTATION_VERSION, '2.1.0', '<' ) || ! defined( '\\Deckerweb\\GitHubReleaseUpdater\\V2\\Updater::SUPPORTS_HOST_TRANSLATIONS' ) || ! \Deckerweb\GitHubReleaseUpdater\V2\Updater::SUPPORTS_HOST_TRANSLATIONS ) { self::$error = true; return; }
		try {
			$updater = new \Deckerweb\GitHubReleaseUpdater\V2\Updater(
				DDW_BDQN_FILE, 'https://github.com/deckerweb/breakdance-quicknav', 'Breakdance QuickNav',
				__( 'Quick access to Breakdance content, templates, settings, and official add-ons from the WordPress toolbar.', 'breakdance-quicknav' ),
				array( 'icons' => array( 'default' => plugins_url( 'assets/brand/icon-256.png', DDW_BDQN_FILE ) ), 'banners' => array( 'low' => plugins_url( 'assets/brand/banner-' . ( 0 === strpos( determine_locale(), 'de' ) ? 'de' : 'en' ) . '-772x250.png', DDW_BDQN_FILE ), 'high' => plugins_url( 'assets/brand/banner-' . ( 0 === strpos( determine_locale(), 'de' ) ? 'de' : 'en' ) . '-1544x500.png', DDW_BDQN_FILE ) ) ),
				array( 'translate' => require DDW_BDQN_DIR . '/includes/updater-translations.php' )
			);
			$updater->register();
			$registered = true;
			add_filter( 'upgrader_source_selection', array( __CLASS__, 'validate_update_source' ), 30, 4 );
			add_filter( 'plugins_api', array( __CLASS__, 'information' ), 30, 3 );
			add_filter( 'http_request_args', array( __CLASS__, 'request_limits' ), 20, 2 );
		} catch ( \Throwable $error ) { self::$error = true; }
	}

	/**
	 * Bound repository metadata without touching unrelated HTTP requests.
	 * @param array $args WordPress request arguments.
	 * @param string $url Request URL.
	 * @return array Scoped request limits.
	 */
	public static function request_limits( $args, $url ) {
		if ( 'https://api.github.com/repos/deckerweb/breakdance-quicknav/releases/latest' === $url ) {
			$args['sslverify'] = true; $args['timeout'] = 6; $args['redirection'] = 2; $args['limit_response_size'] = 1024 * 1024;
		}
		return $args;
	}

	/**
	 * Render local categorized release history in WordPress plugin information.
	 * @param object|false|\WP_Error $result Existing response.
	 * @param string $action Plugin API action.
	 * @param object $args Requested plugin information arguments.
	 * @return object|false|\WP_Error Augmented own-plugin response or original value.
	 */
	public static function information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || 'breakdance-quicknav' !== ( $args->slug ?? '' ) || ! is_object( $result ) || is_wp_error( $result ) ) { return $result; }
		$history = require DDW_BDQN_DIR . '/includes/plugin-history.php';
		$result->sections['changelog'] = $history( 0 === strpos( determine_locale(), 'de' ) );
		return $result;
	}

	/**
	 * Validate an offered candidate after updater directory normalization.
	 *
	 * @param string|WP_Error $source Extracted plugin directory or previous failure.
	 * @param string $remote_source Original extracted download directory.
	 * @param WP_Upgrader $upgrader Native upgrader performing the operation.
	 * @param array $hook_extra Per-package plugin/update context, including bulk variants.
	 * @return string|WP_Error Original source on success, or a localized rejection before replacement.
	 */
	public static function validate_update_source( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( is_wp_error( $source ) || ! is_string( $source ) || ! is_array( $hook_extra )
			|| ( $hook_extra['plugin'] ?? '' ) !== plugin_basename( DDW_BDQN_FILE )
			|| ( isset( $hook_extra['type'] ) && 'plugin' !== $hook_extra['type'] )
			|| ( isset( $hook_extra['action'] ) && 'update' !== $hook_extra['action'] )
			|| ( ! isset( $hook_extra['type'], $hook_extra['action'] ) && ! ( $upgrader instanceof \Plugin_Upgrader && true === $upgrader->bulk ) ) ) {
			return $source;
		}
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return new \WP_Error( 'bdqn_update_filesystem', __( 'Could not access the update filesystem.', 'breakdance-quicknav' ) );
		}
		$root = untrailingslashit( $source );
		if ( $wp_filesystem->is_file( $root . '/breakdance-quicknav/breakdance-quicknav.php' ) ) {
			$root .= '/breakdance-quicknav';
		}
		$main = $root . '/breakdance-quicknav.php';
		$size = $wp_filesystem->size( $main );
		if ( ! is_numeric( $size ) || $size < 1 || $size > 256 * 1024 ) {
			return new \WP_Error( 'bdqn_update_identity', __( 'The update package is not Breakdance QuickNav.', 'breakdance-quicknav' ) );
		}
		$contents = $wp_filesystem->get_contents( $main );
		$headers = array();
		foreach ( array( 'Plugin Name', 'Update URI', 'Requires at least', 'Requires PHP', 'Requires CP', 'Version' ) as $header ) {
			preg_match( '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':[ \t]*(.+)$/mi', is_string( $contents ) ? substr( $contents, 0, 8192 ) : '', $match );
			$headers[ $header ] = isset( $match[1] ) ? trim( $match[1] ) : '';
		}
		if ( 'Breakdance QuickNav' !== $headers['Plugin Name'] || 'https://github.com/deckerweb/breakdance-quicknav' !== $headers['Update URI'] || ! preg_match( '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/D', $headers['Version'] ) ) {
			return new \WP_Error( 'bdqn_update_identity', __( 'The update package is not Breakdance QuickNav.', 'breakdance-quicknav' ) );
		}
		global $wp_version;
		if ( ! preg_match( '/^\d+(?:\.\d+){1,2}$/D', $headers['Requires PHP'] ) || version_compare( PHP_VERSION, $headers['Requires PHP'], '<' ) || ! preg_match( '/^\d+(?:\.\d+){1,2}$/D', $headers['Requires at least'] ) || ( ! function_exists( 'classicpress_version' ) && version_compare( $wp_version, $headers['Requires at least'], '<' ) ) ) {
			return new \WP_Error( 'bdqn_update_compatibility', __( 'The update package requires a newer WordPress or PHP version.', 'breakdance-quicknav' ) );
		}
		if ( function_exists( 'classicpress_version' ) && ( ! preg_match( '/^\d+(?:\.\d+){1,2}$/D', $headers['Requires CP'] ) || version_compare( classicpress_version(), $headers['Requires CP'], '<' ) ) ) {
			return new \WP_Error( 'bdqn_update_compatibility', __( 'The update package requires a newer ClassicPress version.', 'breakdance-quicknav' ) );
		}
		$updates = get_site_transient( 'update_plugins' );
		$offer = is_object( $updates ) ? ( $updates->response[ plugin_basename( DDW_BDQN_FILE ) ] ?? null ) : null;
		$version = is_object( $offer ) ? ( $offer->new_version ?? $offer->version ?? '' ) : '';
		if ( ! is_string( $version ) || '' === $version || $version !== $headers['Version'] || ! version_compare( $headers['Version'], DDW_BDQN_VERSION, '>' ) ) {
			return new \WP_Error( 'bdqn_update_version', __( 'The update package does not match the offered newer version.', 'breakdance-quicknav' ) );
		}
		foreach ( array( 'includes/deckerweb-github-release-updater-v2.php', 'includes/updater-translations.php', 'includes/plugin-history.php', 'includes/history.json', 'includes/class-settings.php', 'includes/class-builder.php', 'includes/class-plugin.php', 'includes/class-updates.php', 'includes/class-activation.php', 'images/breakdance-icon.png', 'includes/deckerweb-plugin-library/bootstrap.php', 'includes/deckerweb-plugin-library/compatibility.json', 'uninstall.php', 'assets/navigation.svg', 'assets/brand/icon.svg', 'assets/brand/icon-128.png', 'assets/brand/icon-256.png', 'assets/brand/banner-en.png', 'assets/brand/banner-de.png', 'assets/brand/banner-en-772x250.png', 'assets/brand/banner-de-772x250.png', 'assets/brand/banner-en-1544x500.png', 'assets/brand/banner-de-1544x500.png', 'assets/quicknav.css', 'assets/dialog.js', 'languages/breakdance-quicknav-de_DE.mo', 'languages/breakdance-quicknav-de_DE_formal.mo' ) as $file ) {
			if ( ! $wp_filesystem->is_file( $root . '/' . $file ) ) {
				return new \WP_Error( 'bdqn_update_incomplete', __( 'The update package is missing required plugin files.', 'breakdance-quicknav' ) );
			}
		}
		$manifest_file = $root . '/includes/deckerweb-plugin-library/compatibility.json';
		if ( $wp_filesystem->size( $manifest_file ) > 64 * 1024 ) {
			return new \WP_Error( 'bdqn_update_incomplete', __( 'The update package is missing required plugin files.', 'breakdance-quicknav' ) );
		}
		$manifest = json_decode( (string) $wp_filesystem->get_contents( $manifest_file ), true );
		if ( ! is_array( $manifest ) || 2 !== ( $manifest['protocol'] ?? null ) || ! is_array( $manifest['files'] ?? null ) || ! is_array( $manifest['hashes'] ?? null ) || count( $manifest['files'] ) > 200 ) {
			return new \WP_Error( 'bdqn_update_incomplete', __( 'The update package is missing required plugin files.', 'breakdance-quicknav' ) );
		}
		$required = array( 'bootstrap.php', 'runtime.php', 'lifecycle.php', 'version.php', 'src/Library.php', 'src/Catalog.php', 'src/Package.php', 'src/Requirements.php', 'src/I18n.php', 'src/History.php', 'catalog.json', 'languages/de_DE.mo', 'languages/de_DE_formal.mo' );
		if ( count( array_filter( $manifest['files'], 'is_string' ) ) !== count( $manifest['files'] )
			|| ! is_string( $manifest['version'] ?? null ) || ! is_string( $manifest['requires_php'] ?? null ) || ! is_string( $manifest['requires_wp'] ?? null )
			|| array_diff( $required, $manifest['files'] ) || ! preg_match( '/^\d+\.\d+\.\d+$/D', $manifest['version'] ?? '' )
			|| ! preg_match( '/^\d+(?:\.\d+){1,2}$/D', $manifest['requires_php'] ?? '' )
			|| ! preg_match( '/^\d+(?:\.\d+){1,2}$/D', $manifest['requires_wp'] ?? '' )
			 ) {
			return new \WP_Error( 'bdqn_update_incomplete', __( 'The update package is missing required plugin files.', 'breakdance-quicknav' ) );
		}
		$total = 0;
		foreach ( $manifest['files'] as $file ) {
			$expected = is_string( $file ) ? ( $manifest['hashes'][ $file ] ?? '' ) : '';
			if ( ! is_string( $file ) || ! preg_match( '~^[a-zA-Z0-9_/.-]+$~D', $file ) || false !== strpos( $file, '..' ) || ! is_string( $expected ) || ! preg_match( '/^[a-f0-9]{64}$/D', $expected ) ) {
				return new \WP_Error( 'bdqn_update_incomplete', __( 'The update package is missing required plugin files.', 'breakdance-quicknav' ) );
			}
			$path = $root . '/includes/deckerweb-plugin-library/' . $file;
			$size = $wp_filesystem->size( $path );
			$total += is_numeric( $size ) ? (int) $size : 0;
			if ( $total > 16 * 1024 * 1024 || ! $wp_filesystem->is_file( $path ) || ! is_numeric( $size ) || $size > 2 * 1024 * 1024 || ! hash_equals( $expected, hash( 'sha256', (string) $wp_filesystem->get_contents( $path ) ) ) ) {
				return new \WP_Error( 'bdqn_update_incomplete', __( 'The update package is missing required plugin files.', 'breakdance-quicknav' ) );
			}
		}
		return $source;
	}

}
