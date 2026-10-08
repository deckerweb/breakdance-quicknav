<?php
/** Verified addon navigation adapters. @package BreakdanceQuickNav */
namespace Deckerweb\BreakdanceQuickNav;

defined( 'ABSPATH' ) || exit;

/** Resolves loaded addons and authorized local admin destinations without HTTP requests. */
final class Integrations {
	/** @return array Adapter definitions; unverified premium packages have no guessed targets. */
	public static function definitions() {
		$font = class_exists( '\JooosiFon\Plugin', false );
		$defs = array(
			'headspin' => array( 'label' => 'Headspin Copilot', 'constant' => 'HSF_VERSION', 'targets' => array( self::target( 'headspin', 'admin.php', 'manage_options', defined( 'HSF_VERSION' ) ) ), 'legacy' => true ),
			'yabe-webfont' => array( 'label' => $font ? 'Jooosi Fon (Yabe Webfont)' : 'Yabe Webfont / Jooosi Fon', 'files' => array( 'yabe-webfont/jooosi-fon.php', 'yabe-webfont/yabe-webfont.php' ), 'loaded' => $font || class_exists( '\Yabe\Webfont\Plugin' ), 'targets' => array( self::target( $font ? 'jooosi_fon' : 'yabe_webfont', $font ? 'admin.php' : 'themes.php', $font ? 'manage_options' : 'edit_theme_options', $font || class_exists( '\Yabe\Webfont\Plugin' ) ) ), 'legacy' => ! $font, 'fallback' => ! $font ),
			'sitecare' => array( 'label' => 'SiteCare Builder Tools', 'files' => array( 'sitecare-builder-tools-for-breakdance/sitecare-builder-tools-for-breakdance.php' ), 'constant' => 'SBHT_VERSION', 'targets' => array( self::target( 'sitecare-breakdance-hide-topbar', Builder::active() ? 'admin.php' : 'options-general.php', 'manage_options', function_exists( 'sbht_render_page' ) ) ) ),
			'elements-hive' => array( 'label' => 'Elements Hive', 'files' => array( 'elements-hive-for-breakdance/elements_hive_for_breakdance.php' ), 'loaded' => class_exists( '\EHForBreakdance', false ), 'targets' => array( self::target( 'elements_hive', 'admin.php', 'manage_options', function_exists( '\ElementsHiveForBreakdance\Admin\Pages\Home\render' ) ) ), 'children' => array( self::target( 'elements_hive_cloudflare_turnstile', 'admin.php', 'manage_options', function_exists( '\ElementsHiveForBreakdance\Admin\Pages\CloudflareTurnstile\render' ), 'Cloudflare Turnstile' ) ) ),
			'express' => array( 'label' => 'Express Add On', 'files' => array( 'express-add-on/vxn-express.php' ), 'loaded' => defined( 'VXN_EXPRESS_ADDON_PLUGIN_FILE' ), 'targets' => self::express_targets(), 'children' => self::express_targets( true ) ),
			'breakcolorui' => array( 'label' => 'BreakColorUI Sync', 'files' => array( 'breakcolorui-sync/breakcolorui-sync.php' ), 'constant' => 'BCUI_SYNC_VERSION', 'targets' => array( self::target( 'breakcolorui-sync', 'admin.php', 'manage_options', class_exists( '\BCUI_Sync_Plugin', false ) ) ) ),
			'wpml' => array( 'label' => 'Breakdance WPML Integration', 'files' => array( 'integration-for-breakdance-and-wpml/integration-for-breakdance-and-wpml.php' ), 'constant' => 'BDWPML_VERSION', 'targets' => array( self::target( 'template-translations', 'admin.php', 'manage_options', class_exists( '\FranNieto\BreakdanceWpmlIntegration\Plugin', false ) && defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'WPML_ST_VERSION' ) ) ) ),
			'wpsix-exporter' => array( 'label' => 'WPSix Exporter', 'files' => array( 'wpsix-exporter/plugin.php' ), 'constant' => 'WPSIX_EXPORTER_VERSION', 'targets' => array( self::target( defined( 'WPSIX_EXPORTER_VERSION' ) && version_compare( WPSIX_EXPORTER_VERSION, '2.0.0', '>=' ) ? 'wpsix-exporter' : 'wpsix_exporter', 'admin.php', 'administrator', defined( 'WPSIX_EXPORTER_URL' ) ) ), 'legacy' => true, 'fallback' => ! defined( 'WPSIX_EXPORTER_VERSION' ) || version_compare( WPSIX_EXPORTER_VERSION, '2.0.0', '<' ) ),
			'bdrtc' => array( 'label' => __( 'Reading Time Calculator', 'breakdance-quicknav' ), 'loaded' => function_exists( 'bd_reading_time_menu' ), 'targets' => array( self::target( 'bd-reading-time', 'admin.php', 'manage_options', function_exists( 'bd_reading_time_menu' ) ) ), 'legacy' => true ),
			'ai' => array( 'label' => 'Breakdance AI', 'files' => array( 'breakdance-ai/breakdance-ai.php' ), 'constant' => 'BREAKDANCE_AI_VERSION', 'tab' => 'ai' ),
			'migration-mode' => array( 'label' => 'Breakdance Migration Mode', 'loaded' => function_exists( '\Breakdance\MigrationMode\saveActivatingUserIp' ), 'tab' => 'migration-mode', 'legacy' => true ),
			'wpsix-elements' => array( 'label' => 'WPSix Elements', 'files' => array( 'wpsix-elements/plugin.php' ), 'constant' => 'WPSIX_ELEMENTS_VERSION', 'no_menu' => true ),
			'image-replace' => array( 'label' => 'Image Replacer', 'files' => array( 'image-replace/plugin.php' ), 'constant' => 'IMAGE_REPLACE_VERSION', 'no_menu' => true ),
		);
		foreach ( array( 'breakmade' => 'BreakMade', 'dancepad' => 'Dancepad', 'smithy' => 'Smithy Portal / Connect', 'phox' => 'Phox Elements', 'builder-languages' => 'Builder Languages for Breakdance' ) as $id => $label ) { $defs[ $id ] = array( 'label' => $label, 'pending' => true ); }
		/**
		 * Register custom integrations with loaded, files, targets, children and capability checks.
		 * @since 2.0.0
		 * @param array $defs Addon adapters. Targets accept slug, file, capability, ready and label.
		 */
		$defs = apply_filters( 'ddw/quicknav/bd_integrations', $defs );
		return is_array( $defs ) ? $defs : array();
	}

	/** @param string $slug Page slug. @param string $file Admin file. @param string $cap Capability. @param bool $ready Loaded endpoint. @param string $label Label. @return array Target descriptor. */
	private static function target( $slug, $file, $cap, $ready, $label = '' ) {
		return array( 'slug' => $slug, 'file' => $file, 'capability' => $cap, 'ready' => $ready, 'label' => $label );
	}

	/** @param bool $children Whether to return enabled module pages. @return array Targets from Express's public runtime registry. */
	private static function express_targets( $children = false ) {
		if ( ! is_callable( array( '\VXN\Express', 'menu_pages' ) ) ) { return array(); }
		$out = array();
		foreach ( \VXN\Express::menu_pages() as $page ) {
			if ( ! $page instanceof \ArrayAccess ) { continue; }
			$slug = $page['slug'];
			if ( ( $children ? 'vxn_express_setup' === $slug : 'vxn_express_setup' !== $slug ) ) { continue; }
			$out[] = self::target( $slug, 'admin.php', $page['capability'], true, $page['menu_title'] );
		}
		return array_slice( $out, 0, 20 );
	}

	/** @param string $id Adapter ID. @param array|null $settings Effective settings. @return bool Whether this website enables its QuickNav links. */
	public static function enabled( $id, $settings = null ) {
		$settings = is_array( $settings ) ? $settings : Settings::get();
		return ! isset( $settings['integrations'][ $id ] ) || ! empty( $settings['integrations'][ $id ] );
	}

	/** @param array $target Source-verified endpoint. @param bool $legacy Preserve original integrations before menu registration. @return string Authorized local URL or empty. */
	public static function destination( $target, $legacy = false ) {
		if ( ! is_array( $target ) ) { return ''; }
		$slug = $target['slug'] ?? ''; $file = $target['file'] ?? 'admin.php'; $cap = $target['capability'] ?? '';
		if ( ! is_string( $slug ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $slug ) || ! in_array( $file, array( 'admin.php', 'themes.php', 'options-general.php', 'tools.php' ), true ) || ! is_string( $cap ) || ! $cap || ! current_user_can( $cap ) || true !== ( $target['ready'] ?? false ) || is_network_admin() || is_user_admin() ) { return ''; }
		global $menu, $submenu;
		foreach ( is_array( $submenu ) ? $submenu : array() as $parent => $items ) {
			foreach ( $items as $item ) {
				if ( ( $item[2] ?? '' ) !== $slug ) { continue; }
				if ( ! current_user_can( $item[1] ) ) { return ''; }
				$entry = in_array( $parent, array( 'themes.php', 'options-general.php', 'tools.php' ), true ) ? $parent : 'admin.php';
				return add_query_arg( 'page', $slug, admin_url( $entry ) );
			}
		}
		foreach ( is_array( $menu ) ? $menu : array() as $item ) { if ( ( $item[2] ?? '' ) === $slug ) { return current_user_can( $item[1] ) ? add_query_arg( 'page', $slug, admin_url( 'admin.php' ) ) : ''; } }
		// Never invent a missing new admin menu. Frontend uses source-verified loaded endpoints.
		if ( is_admin() && did_action( 'admin_menu' ) && ! $legacy ) { return ''; }
		return add_query_arg( 'page', $slug, admin_url( $file ) );
	}

	/** @return array Normalized discovery/status rows and authorized destinations. */
	public static function rows() {
		$out = array(); $active_files = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) { $active_files = array_merge( $active_files, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) ); }
		foreach ( self::definitions() as $id => $def ) {
			if ( ! is_string( $id ) || sanitize_key( $id ) !== $id || ! is_array( $def ) || ! is_string( $def['label'] ?? null ) ) { continue; }
			$constant = is_string( $def['constant'] ?? null ) ? $def['constant'] : '';
			$loaded = ! empty( $def['loaded'] ) || ( $constant && defined( $constant ) ); $installed = $loaded; $active = $loaded; $version = '';
			if ( $constant && defined( $constant ) && is_scalar( constant( $constant ) ) ) { $version = sanitize_text_field( (string) constant( $constant ) ); }
			foreach ( is_array( $def['files'] ?? null ) ? $def['files'] : array() as $file ) {
				if ( ! is_string( $file ) || false !== strpos( $file, '..' ) || ! preg_match( '#^[a-zA-Z0-9_-]+/[a-zA-Z0-9_.-]+\.php$#', $file ) ) { continue; }
				$installed = $installed || is_file( WP_PLUGIN_DIR . '/' . $file ); $active = $active || in_array( $file, $active_files, true );
				if ( ! $version && is_file( WP_PLUGIN_DIR . '/' . $file ) ) { $header = get_file_data( WP_PLUGIN_DIR . '/' . $file, array( 'Version' => 'Version' ) ); $version = $header['Version']; }
			}
			$links = array();
			if ( $active && $loaded && empty( $def['pending'] ) && Builder::active() ) {
				if ( ! empty( $def['tab'] ) && is_string( $def['tab'] ) && isset( Builder::tabs( 'breakdance' )[ $def['tab'] ] ) && current_user_can( 'manage_options' ) && Builder::full_access() ) { $links[] = array( 'label' => $def['label'], 'url' => Builder::settings_url( 'breakdance', $def['tab'] ), 'tab' => $def['tab'] ); }
				foreach ( array_slice( is_array( $def['targets'] ?? null ) ? $def['targets'] : array(), 0, 20 ) as $target ) { $url = self::destination( $target, ! empty( $def['fallback'] ) || ( ! empty( $def['legacy'] ) && empty( $def['files'] ) ) ); if ( $url ) { $links[] = array( 'label' => $def['label'], 'url' => $url ); break; } }
			}
			$children = array();
			if ( $links ) { foreach ( array_slice( is_array( $def['children'] ?? null ) ? $def['children'] : array(), 0, 20 ) as $target ) { $url = self::destination( $target ); if ( $url && is_string( $target['label'] ?? null ) && $target['label'] ) { $children[] = array( 'label' => $target['label'], 'url' => $url ); } } }
			$status = ! empty( $def['pending'] ) ? 'pending' : ( ! $installed ? 'missing' : ( ! $active ? 'inactive' : ( ! Builder::active() ? 'builder' : ( ! empty( $def['no_menu'] ) ? 'no-menu' : ( $links ? 'available' : 'unavailable' ) ) ) ) );
			$out[ $id ] = array( 'label' => $def['label'], 'version' => $version, 'active' => $active, 'status' => $status, 'links' => $links, 'children' => $children, 'pending' => ! empty( $def['pending'] ), 'legacy' => ! empty( $def['legacy'] ) );
		}
		return $out;
	}

	/** @param string $status Normalized availability status. @return string Human-readable translated explanation. */
	public static function status_label( $status ) {
		$labels = array( 'pending' => __( 'Package verification pending', 'breakdance-quicknav' ), 'missing' => __( 'Not installed or not detected', 'breakdance-quicknav' ), 'inactive' => __( 'Installed, inactive', 'breakdance-quicknav' ), 'builder' => __( 'Waiting for a supported Breakdance builder', 'breakdance-quicknav' ), 'no-menu' => __( 'Active; no separate admin page', 'breakdance-quicknav' ), 'available' => __( 'Active; admin destination available', 'breakdance-quicknav' ), 'unavailable' => __( 'Active; admin destination unavailable (permissions, dependencies or disabled module)', 'breakdance-quicknav' ) );
		return $labels[ $status ] ?? $labels['unavailable'];
	}
}
