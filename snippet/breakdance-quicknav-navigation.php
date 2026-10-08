<?php
/** Breakdance QuickNav navigation-only snippet, GPL-2.0-or-later.
 * © 2025–2026 David Decker – DECKERWEB.
 * Origin: Peter Kulcsár, Breakdance Navigator, © 2024, GPL v2 or later.
 * https://github.com/beamkiller/breakdance-navigator
 * Do not run beside the plugin. No settings UI, updater or Library.
 */
namespace Deckerweb\BreakdanceQuickNavSnippet;
if ( ! defined( 'ABSPATH' ) ) { return; }
if ( defined( 'DDW_BDQN_VERSION' ) || class_exists( 'DDW_Breakdance_QuickNav' ) || class_exists( __NAMESPACE__ . '\SnippetPlugin', false ) ) { return; }

if ( ! class_exists( __NAMESPACE__ . '\SnippetPlugin', false ) ) {

/**
 * Host integration. @package BreakdanceQuickNav
 * Adapted from Oxygen QuickNav 2.0.0, © 2025–2026 David Decker – DECKERWEB.
 * GPL-2.0-or-later. https://github.com/deckerweb/oxygen-quicknav/tree/v2.0.0
 */


defined( 'ABSPATH' ) || exit;

/** Validates settings, resolves legacy constants, and stores personal preferences. */
final class SnippetSettings {
	const OPTION = 'ddw_bdqn_settings';
	const USER_OPTION = 'ddw_bdqn_preferences';

	/** @return array Default website configuration; no database writes. */
	public static function defaults() {
		return array(
			'name' => 'BD', 'icon' => 'auto', 'limit' => 20, 'orderby' => 'modified',
			'backend' => true, 'frontend' => true, 'footer' => true, 'drafts' => false,
			'capability' => 'activate_plugins', 'users' => array(), 'post_types' => array( 'page' ),
			'groups' => array( 'content', 'templates', 'headers', 'footers', 'global-blocks', 'popups', 'settings', 'addons' ),
			'delete_data' => false, 'integrations' => array(), 'integration_direct' => array(), 'integration_children' => true,
		);
	}

	/**
	 * Validate an option array against the supported configuration.
	 * @param mixed $input Submitted or stored configuration.
	 * @return array Complete sanitized website configuration.
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out = self::defaults();
		foreach ( array( 'backend', 'frontend', 'footer', 'drafts', 'delete_data', 'integration_children' ) as $key ) {
			$out[ $key ] = ! empty( $input[ $key ] );
		}
		$out['name'] = isset( $input['name'] ) && is_scalar( $input['name'] ) ? sanitize_text_field( (string) $input['name'] ) : 'BD';
		if ( '' === $out['name'] ) { $out['name'] = 'BD'; }
		$out['name'] = function_exists( 'mb_substr' ) ? mb_substr( $out['name'], 0, 40 ) : substr( $out['name'], 0, 40 );
		$out['limit'] = isset( $input['limit'] ) && is_scalar( $input['limit'] ) ? min( 100, max( 1, absint( $input['limit'] ) ) ) : 20;
		$out['icon'] = in_array( $input['icon'] ?? '', array( 'auto', 'yellow', 'none' ), true ) ? $input['icon'] : 'auto';
		$out['orderby'] = in_array( $input['orderby'] ?? '', array( 'modified', 'title' ), true ) ? $input['orderby'] : 'modified';
		$out['capability'] = isset( $input['capability'] ) && is_string( $input['capability'] ) ? sanitize_key( $input['capability'] ) : 'activate_plugins';
		if ( '' === $out['capability'] ) { $out['capability'] = 'activate_plugins'; }
		$users = $input['users'] ?? array();
		if ( is_string( $users ) ) { $users = preg_split( '/[\s,;]+/', $users ); }
		$out['users'] = is_array( $users ) ? array_values( array_unique( array_filter( array_map( 'absint', array_filter( $users, 'is_scalar' ) ) ) ) ) : array();
		$types = get_post_types( array( 'public' => true ), 'names' );
		$out['post_types'] = is_array( $input['post_types'] ?? null ) ? array_values( array_intersect( array_filter( $input['post_types'], 'is_string' ), $types ) ) : array( 'page' );
		$out['groups'] = is_array( $input['groups'] ?? null ) ? array_values( array_intersect( array_filter( $input['groups'], 'is_string' ), self::defaults()['groups'] ) ) : self::defaults()['groups'];
		foreach ( array( 'integrations', 'integration_direct' ) as $map ) {
		foreach ( is_array( $input[ $map ] ?? null ) ? array_slice( $input[ $map ], 0, 100, true ) : array() as $id => $enabled ) {
			if ( is_string( $id ) && sanitize_key( $id ) === $id && in_array( $enabled, array( true, false, 1, 0, '1', '0' ), true ) ) { $out[ $map ][ $id ] = (bool) $enabled; }
		}
		}
		return $out;
	}

	/**
	 * Resolve website values, user preferences, and immutable legacy overrides.
	 * @param bool $personal Whether to apply the current user's site-scoped preferences.
	 * @return array Effective settings, with constants taking final precedence.
	 */
	public static function get( $personal = true ) {
		$out = self::defaults();
		$map = self::constant_map();
		foreach ( $map as $key => $constant ) {
			if ( ! defined( $constant ) ) { continue; }
			$value = constant( $constant );
			if ( 'footer' === $key ) { $out['footer'] = 'yes' !== $value; }
			elseif ( 'users' === $key ) {
				$out['users'] = is_array( $value ) ? array_values( array_filter( array_map( 'absint', array_filter( $value, 'is_scalar' ) ) ) ) : array( -1 );
				// An explicitly empty legacy allowlist continues to deny every user.
				if ( empty( $out['users'] ) ) { $out['users'] = array( -1 ); }
			} elseif ( 'limit' === $key ) { $out['limit'] = is_scalar( $value ) ? min( 100, max( 1, absint( $value ) ) ) : 20; }
			elseif ( is_scalar( $value ) ) {
				if ( 'icon' === $key ) { $out[ $key ] = 'yellow' === $value ? 'yellow' : 'auto'; }
				elseif ( 'capability' === $key ) { $out[ $key ] = sanitize_key( (string) $value ); }
				else { $out[ $key ] = sanitize_text_field( (string) $value ); }
			}
		}
		return $out;
	}

	/** @return array Setting keys mapped to supported legacy constants. */
	public static function constant_map() {
		return array( 'name' => 'BDQN_NAME_IN_ADMINBAR', 'icon' => 'BDQN_ICON', 'limit' => 'BDQN_NUMBER_TEMPLATES', 'capability' => 'BDQN_VIEW_CAPABILITY', 'users' => 'BDQN_ENABLED_USERS', 'footer' => 'BDQN_DISABLE_FOOTER' );
	}
}

/** Read-only Breakdance integration. @package BreakdanceQuickNav */


defined( 'ABSPATH' ) || exit;

/** Resolves supported Breakdance interfaces without altering builder data or files. */
final class SnippetBuilder {
	/** @return array Supported active builder identifiers; Oxygen mode is excluded. */
	public static function active() {
		if ( ! defined( '__BREAKDANCE_VERSION' ) || ! is_string( __BREAKDANCE_VERSION )
			|| ( defined( 'BREAKDANCE_MODE' ) && 'breakdance' !== BREAKDANCE_MODE )
			|| ! preg_match( '/^[23]\./', __BREAKDANCE_VERSION )
			|| ! function_exists( '\Breakdance\Admin\get_builder_loader_url' ) ) { return array(); }
		return array( 'breakdance' );
	}

	/**
	 * Resolve the existing template archives for the active builder.
	 * @param string $generation Supported builder identifier.
	 * @return array Group keys mapped to label, post type and archive URL.
	 */
	public static function groups( $generation ) {
		return array(
			'templates' => array( __( 'Templates', 'breakdance-quicknav' ), 'breakdance_template', admin_url( 'admin.php?page=breakdance_template' ) ),
			'headers' => array( __( 'Headers', 'breakdance-quicknav' ), 'breakdance_header', admin_url( 'admin.php?page=breakdance_header' ) ),
			'footers' => array( __( 'Footers', 'breakdance-quicknav' ), 'breakdance_footer', admin_url( 'admin.php?page=breakdance_footer' ) ),
			'global-blocks' => array( __( 'Global Blocks', 'breakdance-quicknav' ), 'breakdance_block', admin_url( 'admin.php?page=breakdance_block' ) ),
			'popups' => array( __( 'Popups', 'breakdance-quicknav' ), 'breakdance_popup', admin_url( 'admin.php?page=breakdance_popup' ) ),
		);
	}

	/** @return bool Whether the current user has full Breakdance interface access. */
	public static function full_access() {
		return function_exists( '\Breakdance\Permissions\hasPermission' ) && \Breakdance\Permissions\hasPermission( 'full' );
	}

	/**
	 * Enforce WordPress object and Breakdance permissions for a builder target.
	 * @param int $id Post ID.
	 * @param string $generation Supported builder identifier.
	 * @return bool Whether this user may edit the target using Breakdance.
	 */
	public static function can_edit( $id, $generation ) {
		return current_user_can( 'edit_post', $id )
			&& function_exists( '\Breakdance\Permissions\hasMinimumPermission' ) && \Breakdance\Permissions\hasMinimumPermission( 'edit' )
			&& function_exists( '\Breakdance\Permissions\isPostTypeAllowed' ) && \Breakdance\Permissions\isPostTypeAllowed( get_post_type( $id ) );
	}

	/**
	 * Use the builder's URL helper after verifying permission.
	 * @param int $id Post ID.
	 * @param string $generation Supported builder identifier.
	 * @return string Builder URL, or empty string when unavailable or unauthorized.
	 */
	public static function edit_url( $id, $generation ) {
		return self::active() && self::can_edit( $id, $generation ) ? \Breakdance\Admin\get_builder_loader_url( $id ) : '';
	}

	/**
	 * Distinguish actual builder content and exclude internal fallback templates.
	 * @param \WP_Post $post Candidate object.
	 * @param string $group Requested group.
	 * @return bool Whether the post belongs to this group.
	 */
	private static function matches( $post, $group ) {
		if ( 'content' === $group ) {
			$data = get_post_meta( $post->ID, '_breakdance_data', true );
			if ( is_string( $data ) ) { $data = json_decode( $data, true ); }
			if ( ! is_array( $data ) || empty( $data ) ) { return false; }
			// A metadata shell with no tree is not an authored Breakdance page.
			if ( isset( $data['tree_json_string'] ) && is_string( $data['tree_json_string'] ) ) {
				$tree = json_decode( $data['tree_json_string'], true );
				return is_array( $tree ) && ! empty( $tree['root']['children'] );
			}
			return ! empty( $data['root']['children'] ) || ! empty( $data['children'] );
		}
		if ( 'templates' === $group ) {
			$settings = get_post_meta( $post->ID, '_breakdance_template_settings', true );
			if ( is_string( $settings ) ) { $settings = json_decode( $settings, true ); }
			return ! ( is_array( $settings ) && ! empty( $settings['fallback'] ) );
		}
		return true;
	}

	/**
	 * Retrieve a bounded list of authorized builder objects, filling filtered gaps.
	 * @param string $type Registered post type.
	 * @param string $generation Supported builder identifier.
	 * @param string $group Content/template group.
	 * @param array $settings Effective website/user settings.
	 * @return array Authorized WP_Post objects, up to the configured limit.
	 */
	public static function posts( $type, $generation, $group, $settings ) {
		if ( ! post_type_exists( $type ) ) { return array(); }
		$args = array( 'post_type' => $type, 'posts_per_page' => min( 100, max( 20, $settings['limit'] * 2 ) ),
			'post_status' => $settings['drafts'] ? array( 'publish', 'draft', 'pending', 'future', 'private' ) : 'publish',
			'orderby' => array( $settings['orderby'] => 'title' === $settings['orderby'] ? 'ASC' : 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => true );
		if ( 'content' === $group ) { $args['meta_query'] = array( array( 'key' => '_breakdance_data', 'compare' => 'EXISTS' ) ); }
		/**
		 * Filter candidate query arguments; return the modified argument array.
		 * @since 1.0.0
		 * @param array $args WordPress query arguments.
		 * @param string $type Requested post type.
		 */
		$args = apply_filters( 'ddw/quicknav/bd_get_template_type', $args, $type );
		if ( ! is_array( $args ) ) { return array(); }
		$args['post_type'] = $type;
		$args['posts_per_page'] = is_scalar( $args['posts_per_page'] ?? null ) ? min( 100, max( 1, absint( $args['posts_per_page'] ) ) ) : 40;
		// Visibility options and post checks remain authoritative after developer filters.
		$args['post_status'] = $settings['drafts'] ? array( 'publish', 'draft', 'pending', 'future', 'private' ) : 'publish';
		$args['no_found_rows'] = true;
		$found = array();
		for ( $page = 1; $page <= 5 && count( $found ) < $settings['limit']; $page++ ) {
			$args['paged'] = $page;
			foreach ( $posts = get_posts( $args ) as $post ) {
				if ( $post instanceof \WP_Post && $post->post_type === $type && in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'future', 'private' ), true )
					&& ( $settings['drafts'] || 'publish' === $post->post_status ) && self::can_edit( $post->ID, $generation ) && self::matches( $post, $group ) ) { $found[ $post->ID ] = $post; }
				if ( count( $found ) >= $settings['limit'] ) { break; }
			}
			if ( count( $posts ) < $args['posts_per_page'] ) { break; }
		}
		return array_values( $found );
	}

	/**
	 * Read the registered settings tabs; license stays last and old filters remain.
	 * @param string $generation Supported builder identifier.
	 * @return array Validated tab slugs mapped to plain-text labels.
	 */
	public static function tabs( $generation ) {
		$tabs = array();
		$class = '\\Breakdance\\Admin\\SettingsPage\\SettingsPageController';
		if ( class_exists( $class ) && is_callable( array( $class, 'getInstance' ) ) ) {
			$controller = $class::getInstance();
			$registered = is_object( $controller ) && isset( $controller->tabs ) ? $controller->tabs : array();
			if ( is_array( $registered ) ) {
				$registered = array_filter( $registered, 'is_array' );
				/** @param array $a First tab. @param array $b Second tab. @return int Tab order. */
				usort( $registered, static function ( $a, $b ) { return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 ); } );
				foreach ( $registered as $tab ) {
					if ( is_string( $tab['slug'] ?? null ) && is_string( $tab['name'] ?? null ) && preg_match( '/^[a-z0-9_-]+$/D', $tab['slug'] ) ) { $tabs[ $tab['slug'] ] = wp_strip_all_tags( $tab['name'] ); }
				}
			}
		}
		/**
		 * Filter settings labels; return a slug-to-label array of navigation targets.
		 * @since 1.0.0
		 * @param array $tabs Registered settings tabs.
		 */
		$tabs = apply_filters( 'ddw/quicknav/bd_settings', $tabs );
		if ( ! is_array( $tabs ) ) { return array(); }
		$out = array();
		foreach ( $tabs as $slug => $label ) { if ( is_string( $slug ) && preg_match( '/^[a-z0-9_-]+$/D', $slug ) && is_string( $label ) ) { $out[ $slug ] = wp_strip_all_tags( $label ); } }
		if ( isset( $out['license'] ) ) { $license = $out['license']; unset( $out['license'] ); $out['license'] = $license; }
		return $out;
	}

	/**
	 * Resolve a settings page URL using the available official helper.
	 * @param string $generation Supported builder identifier.
	 * @param string $tab Optional settings tab.
	 * @return string Settings URL.
	 */
	public static function settings_url( $generation, $tab = '' ) {
		if ( function_exists( '\Breakdance\Admin\SettingsPage\settings_page_url' ) ) { return \Breakdance\Admin\SettingsPage\settings_page_url( sanitize_key( $tab ) ); }
		$args = array( 'page' => 'breakdance_settings' );
		if ( '' !== $tab ) { $args['tab'] = sanitize_key( $tab ); }
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/** @return string Official browse-mode URL with a correctly encoded return target. */
	public static function styles_url() {
		return function_exists( '\Breakdance\Admin\get_browse_mode_url' ) ? \Breakdance\Admin\get_browse_mode_url( null, self::settings_url( 'breakdance', 'global_styles' ) ) : '';
	}

	/** @return array Detected addon names mapped to version/status strings. */
	public static function addons() {
		$out = array();
		foreach ( SnippetIntegrations::rows() as $row ) {
			if ( $row['active'] ) { $out[ $row['label'] ] = $row['version'] ?: __( 'Detected', 'breakdance-quicknav' ); }
		}
		return $out;
	}
}

/** Verified addon navigation adapters. @package BreakdanceQuickNav */


defined( 'ABSPATH' ) || exit;

/** Resolves loaded addons and authorized local admin destinations without HTTP requests. */
final class SnippetIntegrations {
	/** @return array Adapter definitions; unverified premium packages have no guessed targets. */
	public static function definitions() {
		$font = class_exists( '\JooosiFon\Plugin', false );
		$defs = array(
			'headspin' => array( 'label' => 'Headspin Copilot', 'constant' => 'HSF_VERSION', 'targets' => array( self::target( 'headspin', 'admin.php', 'manage_options', defined( 'HSF_VERSION' ) ) ), 'legacy' => true ),
			'yabe-webfont' => array( 'label' => $font ? 'Jooosi Fon (Yabe Webfont)' : 'Yabe Webfont / Jooosi Fon', 'files' => array( 'yabe-webfont/jooosi-fon.php', 'yabe-webfont/yabe-webfont.php' ), 'loaded' => $font || class_exists( '\Yabe\Webfont\Plugin' ), 'targets' => array( self::target( $font ? 'jooosi_fon' : 'yabe_webfont', $font ? 'admin.php' : 'themes.php', $font ? 'manage_options' : 'edit_theme_options', $font || class_exists( '\Yabe\Webfont\Plugin' ) ) ), 'legacy' => ! $font, 'fallback' => ! $font ),
			'sitecare' => array( 'label' => 'SiteCare Builder Tools', 'files' => array( 'sitecare-builder-tools-for-breakdance/sitecare-builder-tools-for-breakdance.php' ), 'constant' => 'SBHT_VERSION', 'targets' => array( self::target( 'sitecare-breakdance-hide-topbar', SnippetBuilder::active() ? 'admin.php' : 'options-general.php', 'manage_options', function_exists( 'sbht_render_page' ) ) ) ),
			'elements-hive' => array( 'label' => 'Elements Hive', 'files' => array( 'elements-hive-for-breakdance/elements_hive_for_breakdance.php' ), 'loaded' => class_exists( '\EHForBreakdance', false ), 'targets' => array( self::target( 'elements_hive', 'admin.php', 'manage_options', function_exists( '\ElementsHiveForBreakdance\Admin\Pages\Home\render' ) ) ), 'children' => array( self::target( 'elements_hive_cloudflare_turnstile', 'admin.php', 'manage_options', function_exists( '\ElementsHiveForBreakdance\Admin\Pages\CloudflareTurnstile\render' ), 'Cloudflare Turnstile' ) ) ),
			'elements-hive-pro' => array( 'label' => 'Elements Hive Pro', 'files' => array( 'elements-hive-for-breakdance-pro/elements_hive_for_breakdance_pro.php' ), 'constant' => 'ELEMENTS_HIVE_PRO_VERSION', 'targets' => array( self::target( 'elements_hive_pro', 'admin.php', 'manage_options', function_exists( '\ElementsHiveForBreakdancePro\Admin\render' ) ) ), 'children' => array( self::target( 'elements_hive_pro_license', 'admin.php', 'manage_options', function_exists( '\ElementsHiveForBreakdancePro\Admin\LicensePage\render' ), __( 'License', 'breakdance-quicknav' ) ), self::target( 'elements_hive_pro_tools', 'admin.php', 'manage_options', function_exists( '\ElementsHiveForBreakdancePro\Admin\ToolsPage\render' ), __( 'Tools', 'breakdance-quicknav' ) ) ) ),
			'destiny-elements' => array( 'label' => 'Destiny Elements', 'files' => array( 'destiny-elements/destiny-elements.php' ), 'loaded' => function_exists( 'destiny_elements_menu' ), 'targets' => array( self::target( 'destiny-elements.php', 'admin.php', 'manage_options', function_exists( 'destiny_elements' ) ) ), 'children' => array( self::target( 'destiny_elements_license', 'admin.php', 'manage_options', function_exists( 'destiny_elements_menu' ) && class_exists( '\Appsero\License', false ), __( 'License', 'breakdance-quicknav' ) ) ) ),
			'dancepad' => array( 'label' => 'Dancepad', 'files' => array( 'dancepad/dancepad.php' ), 'constant' => 'DANCEPAD_VERSION', 'targets' => array( self::target( 'dancepad', 'admin.php', 'manage_options', class_exists( '\Dancepad\Initialize', false ) ) ) ),
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
		foreach ( array( 'breakmade' => 'BreakMade', 'smithy' => 'Smithy Portal / Connect', 'phox' => 'Phox Elements', 'builder-languages' => 'Builder Languages for Breakdance' ) as $id => $label ) { $defs[ $id ] = array( 'label' => $label, 'pending' => true ); }
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
		$settings = is_array( $settings ) ? $settings : SnippetSettings::get();
		return ! isset( $settings['integrations'][ $id ] ) || ! empty( $settings['integrations'][ $id ] );
	}

	/** @param array $target Source-verified endpoint. @param bool $legacy Preserve original integrations before menu registration. @return string Authorized local URL or empty. */
	public static function destination( $target, $legacy = false ) {
		if ( ! is_array( $target ) ) { return ''; }
		$slug = $target['slug'] ?? ''; $file = $target['file'] ?? 'admin.php'; $cap = $target['capability'] ?? '';
		if ( ! is_string( $slug ) || ! preg_match( '/^[a-zA-Z0-9_-]+(?:\.php)?$/', $slug ) || ! in_array( $file, array( 'admin.php', 'themes.php', 'options-general.php', 'tools.php' ), true ) || ! is_string( $cap ) || ! $cap || ! current_user_can( $cap ) || true !== ( $target['ready'] ?? false ) || is_network_admin() || is_user_admin() ) { return ''; }
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
			if ( $active && $loaded && empty( $def['pending'] ) && SnippetBuilder::active() ) {
				if ( ! empty( $def['tab'] ) && is_string( $def['tab'] ) && isset( SnippetBuilder::tabs( 'breakdance' )[ $def['tab'] ] ) && current_user_can( 'manage_options' ) && SnippetBuilder::full_access() ) { $links[] = array( 'label' => $def['label'], 'url' => SnippetBuilder::settings_url( 'breakdance', $def['tab'] ), 'tab' => $def['tab'] ); }
				foreach ( array_slice( is_array( $def['targets'] ?? null ) ? $def['targets'] : array(), 0, 20 ) as $target ) { $url = self::destination( $target, ! empty( $def['fallback'] ) || ( ! empty( $def['legacy'] ) && empty( $def['files'] ) ) ); if ( $url ) { $links[] = array( 'label' => $def['label'], 'url' => $url ); break; } }
			}
			$children = array();
			if ( $links ) { foreach ( array_slice( is_array( $def['children'] ?? null ) ? $def['children'] : array(), 0, 20 ) as $target ) { $url = self::destination( $target ); if ( $url && is_string( $target['label'] ?? null ) && $target['label'] ) { $children[] = array( 'label' => $target['label'], 'url' => $url ); } } }
			// Native addon submenus supplement audited frontend targets after admin registration.
			if ( $links && empty( $links[0]['tab'] ) && is_admin() && did_action( 'admin_menu' ) ) {
				global $submenu;
				$query = array(); parse_str( (string) wp_parse_url( $links[0]['url'], PHP_URL_QUERY ), $query );
				$parent_slug = $query['page'] ?? '';
				foreach ( array_slice( is_array( $submenu[ $parent_slug ] ?? null ) ? $submenu[ $parent_slug ] : array(), 0, 20 ) as $item ) {
					if ( ! is_string( $item[0] ?? null ) || ! is_string( $item[1] ?? null ) || ! is_string( $item[2] ?? null ) ) { continue; }
					$url = self::destination( self::target( $item[2], 'admin.php', $item[1], true ) );
					$label = trim( wp_strip_all_tags( $item[0] ) );
					if ( $url && $url !== $links[0]['url'] && $label ) { $children[] = array( 'label' => $label, 'url' => $url ); }
				}
			}
			$unique = array(); foreach ( $children as $child ) { if ( ! isset( $unique[ $child['url'] ] ) && $child['url'] !== $links[0]['url'] ) { $unique[ $child['url'] ] = $child; } }
			$children = array_slice( array_values( $unique ), 0, 20 );
			$status = ! empty( $def['pending'] ) ? 'pending' : ( ! $installed ? 'missing' : ( ! $active ? 'inactive' : ( ! SnippetBuilder::active() ? 'builder' : ( ! empty( $def['no_menu'] ) ? 'no-menu' : ( $links ? 'available' : 'unavailable' ) ) ) ) );
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

/** Navigation-only coordinator without settings UI or shared components. */
final class SnippetPlugin {
	/** @return void Registers navigation and toolbar styles only. */
	public function __construct() { add_action( 'admin_bar_menu', array( $this, 'toolbar' ), 999 ); add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) ); add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) ); }

	/**
	 * Check the user's toolbar visibility independently of target permissions.
	 * @param array $settings Effective settings.
	 * @return bool Whether QuickNav may render in the current website context.
	 */
	private function visible( $settings ) {
		return ! is_network_admin() && ! is_user_admin() && is_user_logged_in() && is_admin_bar_showing()
			&& ! empty( $settings[ is_admin() ? 'backend' : 'frontend' ] )
			&& current_user_can( $settings['capability'] )
			&& ( empty( $settings['users'] ) || in_array( get_current_user_id(), $settings['users'], true ) );
	}

	/** @param string $hook Admin screen hook. @return void Adds minimal toolbar-only styling. */
	public function assets( $hook = '' ) { if ( SnippetBuilder::active() && $this->visible( SnippetSettings::get() ) ) { wp_add_inline_style( 'admin-bar', '#wpadminbar .bdqn-icon{width:16px;height:16px;vertical-align:middle;margin-right:6px}#wpadminbar .bdqn-status-heading>.ab-empty-item,#wpadminbar .bdqn-status-label{font-size:11px;color:#a7aaad}#wpadminbar .bdqn-status-heading>.ab-empty-item{cursor:default}' ); } }

	/**
	 * Render authorized Breakdance content and settings while preserving native editor behavior.
	 * @param \WP_Admin_Bar $bar WordPress toolbar instance.
	 * @return void Adds host-scoped navigation when the builder and user are eligible.
	 */
	public function toolbar( $bar ) {
		$settings = SnippetSettings::get();
		if ( ! SnippetBuilder::active() || class_exists( 'Breakdance_Navigator' ) || class_exists( 'DDW_Breakdance_QuickNav' ) || ! $this->visible( $settings ) ) { return; }
		$title = esc_html( $settings['name'] );
		if ( 'none' !== $settings['icon'] ) {
			$icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAACs0lEQVRYw92XTUhUURTHf/fNjDOOaWo2JBkG1SJaRG2CoKhcSLiIFlHRohZC0CKIWgSRbVq4aNdKkNrVwgKFqKQwGxkqskDpY0LGT0wtSaeJcd7XvS1mGCcUfU9nFPqv3tzHvPPj/M853CNUZJMG4jBwHTgC+CmsdKAbaAYV9maCtwI7WBv5gXpgJ4hGoSJVzzMH66FOoSJVKTdpVwqm4xJNCCrLBELMv7Ml/JyRBAOCshLhyA7Nrecv35scv/qbhmtxevrM7LlUcO9JimOX45y9lWBgzHZkh+YmuJTw4EWKD1GLd18sHr0ysu9mE4qWjhRfh22evTX+gVtK7gAUzOk5+TPV/LOhSCRV1ibLJv8AhdD/C2DLNQBIGQqZCWQrkDm+Gzn1UTCASL9Fb9QimVJE+k2mZmQOnLNveFcDEBu3OdOUoLZa4/Ogne0CNxnwrtbroQmboYmFPWeY61yEeqFqQCmnAAXIQCKpGJ921l+rqgEp4dOgxcCYxJLpD9k2dH006Y1ajsf2igF6+kzO307wfVpmU66U8+HiRosChPtMRiYla6FFARoOFtERNoiO2ssWnWU799sxwL5dXtqbyxieXB5gNqF4/FqnrcsgmVL5ARACakIaNaHFmyT+R/H0jcHwpOTofh93r2xgS2WSOw/nsO08ACzZ34aiqTVJS3sK3VRsC2ncv1HKpZPFtIcNvo26I3A9iGLjkrYuPTvpxn6kf1duFIQq3A9W1//wesCjLTwTKyxC1wDbqz2cq/cTDKSv5Du2ejhV58fnE1SVz2MEA6IwNVDkg5sXghza62Pql+TAHh+7az1oGlw8EWBkQlJSLDhd5+y273oxcbK0eD2C8lLhxBZdyyyKeZEQsLlco8JZcIBuLb2lEluHC3EMaNYQKgw0Ap2Z1bnQ0jOxGhEq/Bf0mirmvL+7UgAAAABJRU5ErkJggg==';
			if ( 'yellow' !== $settings['icon'] ) {
				// Resolve the actual builder directory; renamed plugin folders remain valid.
				$reflection = new \ReflectionFunction( '\Breakdance\Admin\get_builder_loader_url' );
				$path = dirname( $reflection->getFileName(), 3 ) . '/builder/dist/favicon-dark.svg';
				if ( is_file( $path ) ) { $icon = plugins_url( 'builder/dist/favicon-dark.svg', dirname( $reflection->getFileName(), 3 ) . '/plugin.php' ); }
			}
			$title = '<img class="bdqn-icon" src="' . esc_url( $icon, array( 'http', 'https', 'data' ) ) . '" alt="" width="16" height="16">' . $title;
		}
		$root = 'ddw-breakdance-quicknav';
		$bar->add_node( array( 'id' => $root, 'title' => $title, 'href' => '#', 'meta' => array( 'menu_title' => 'Breakdance QuickNav' ) ) );
		if ( in_array( 'content', $settings['groups'], true ) ) {
			foreach ( $settings['post_types'] as $type ) {
				$object = get_post_type_object( $type );
				if ( ! $object || ! current_user_can( $object->cap->edit_posts ) || ! function_exists( '\Breakdance\Permissions\isPostTypeAllowed' ) || ! \Breakdance\Permissions\isPostTypeAllowed( $type ) ) { continue; }
				$this->content_group( $bar, 'page' === $type ? 'bdqn-pages' : 'bdqn-content-' . $type, $root, $object->labels->name, admin_url( 'edit.php?post_type=' . $type ), $type, 'breakdance', 'content', $settings );
			}
		}
		foreach ( SnippetBuilder::groups( 'breakdance' ) as $group => $info ) {
			$object = get_post_type_object( $info[1] );
			if ( ! SnippetBuilder::full_access() || ! $object || ! current_user_can( $object->cap->edit_posts ) || ! in_array( $group, $settings['groups'], true ) ) { continue; }
			$this->content_group( $bar, 'bdqn-' . $group, $root, $info[0], $info[2], $info[1], 'breakdance', $group, $settings );
		}
		if ( in_array( 'settings', $settings['groups'], true ) ) {
			if ( post_type_exists( 'breakdance_form_res' ) && function_exists( '\Breakdance\Forms\Submission\canViewSubmissions' ) && \Breakdance\Forms\Submission\canViewSubmissions() ) {
				$bar->add_node( array( 'id' => 'bdqn-form-submissions', 'parent' => $root, 'title' => esc_html__( 'Form Submissions', 'breakdance-quicknav' ), 'href' => admin_url( 'edit.php?post_type=breakdance_form_res' ) ) );
				$this->form_nodes( $bar, $settings['limit'] );
			}
			if ( current_user_can( 'manage_options' ) && SnippetBuilder::full_access() ) {
				$url = SnippetBuilder::styles_url();
				if ( $url ) { $bar->add_node( array( 'id' => 'bdqn-edit-global-styles', 'parent' => $root, 'title' => esc_html__( 'Edit Global Styles', 'breakdance-quicknav' ), 'href' => esc_url( $url ), 'meta' => array( 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ) ); }
				if ( function_exists( '\Breakdance\DesignLibrary\hideDesignLibrary' ) && ! \Breakdance\DesignLibrary\hideDesignLibrary() ) { $bar->add_node( array( 'id' => 'bdqn-design-library', 'parent' => $root, 'title' => esc_html__( 'Design Library', 'breakdance-quicknav' ), 'href' => admin_url( 'admin.php?page=breakdance_design_library' ) ) ); }
				$bar->add_node( array( 'id' => 'bdqn-settings', 'parent' => $root, 'title' => esc_html__( 'Breakdance Settings', 'breakdance-quicknav' ), 'href' => SnippetBuilder::settings_url( 'breakdance' ) ) );
				foreach ( SnippetBuilder::tabs( 'breakdance' ) as $tab => $label ) { if ( in_array( $tab, array( 'ai', 'migration-mode' ), true ) && ! SnippetIntegrations::enabled( $tab, $settings ) ) { continue; } $bar->add_node( array( 'id' => 'bdqn-settings-' . $tab, 'parent' => 'bdqn-settings', 'title' => esc_html( $label ), 'href' => esc_url( SnippetBuilder::settings_url( 'breakdance', $tab ) ) ) ); }
			}
		}
		if ( in_array( 'addons', $settings['groups'], true ) ) { $this->addon_nodes( $bar, $root ); }
		if ( $settings['footer'] ) { $this->resources( $bar, $root ); }
	}

	/**
	 * Preserve every original resource and author destination.
	 * @param \WP_Admin_Bar $bar Toolbar instance.
	 * @param string $root Parent node.
	 * @return void Adds external links with protected new-tab navigation.
	 */
	private function resources( $bar, $root ) {
		$bar->add_group( array( 'id' => 'bdqn-group-footer', 'parent' => $root, 'meta' => array( 'class' => 'ab-sub-secondary' ) ) );
		$bar->add_node( array( 'id' => 'bdqn-links', 'parent' => 'bdqn-group-footer', 'title' => esc_html__( 'Links', 'breakdance-quicknav' ) ) );
		$bar->add_node( array( 'id' => 'bdqn-about', 'parent' => 'bdqn-group-footer', 'title' => esc_html__( 'About', 'breakdance-quicknav' ) ) );
		$links = array(
			'breakdance' => array( __( 'Breakdance HQ', 'breakdance-quicknav' ), 'https://breakdance.com/' ),
			'breakdance-learn' => array( __( 'Learn Breakdance (Tutorials)', 'breakdance-quicknav' ), 'https://breakdance.com/learn/' ),
			'breakdance-docs' => array( __( 'Breakdance Documentation', 'breakdance-quicknav' ), 'https://breakdance.com/documentation/' ),
			'breakdance-youtube' => array( __( 'Breakdance YouTube Channel', 'breakdance-quicknav' ), 'https://www.youtube.com/@OfficialBreakdance/featured' ),
			'breakdance-fb-group' => array( __( 'Breakdance FB Group', 'breakdance-quicknav' ), 'https://www.facebook.com/groups/breakdanceofficial' ),
			'breakdance4fun' => array( __( 'breakdance4fun (Tutorials, Tips, Resources)', 'breakdance-quicknav' ), 'https://breakdance4fun.supadezign.com/' ),
			'bd-discord-unofficial' => array( __( 'Breakdance Unofficial Discord', 'breakdance-quicknav' ), 'https://discord.com/channels/523286444283002890/530617461775532042' ),
			'headspin' => array( __( 'Headspin', 'breakdance-quicknav' ), 'https://headspinui.com/' ),
			'moreblocks' => array( __( 'Moreblocks', 'breakdance-quicknav' ), 'https://moreblocks.com/' ),
			'breakerblocks' => array( __( 'Breakerblocks', 'breakdance-quicknav' ), 'https://breakerblocks.com/' ),
			'bdlibraryawesome' => array( __( 'BD Library Awesome', 'breakdance-quicknav' ), 'https://bdlibraryawesome.com/' ),
			'bdblox' => array( __( 'Bdblox', 'breakdance-quicknav' ), 'https://bdblox.com/' ),
		);
		$about = array(
			'author' => array( __( 'Author: David Decker', 'breakdance-quicknav' ), 'https://deckerweb.de/' ),
			'github' => array( __( 'Plugin on GitHub', 'breakdance-quicknav' ), 'https://github.com/deckerweb/breakdance-quicknav' ),
			'kofi' => array( __( 'Buy Me a Coffee', 'breakdance-quicknav' ), 'https://ko-fi.com/deckerweb' ),
		);
		foreach ( array( 'links' => $links, 'about' => $about ) as $group => $items ) {
			foreach ( $items as $key => $item ) { $bar->add_node( array( 'id' => ( 'links' === $group ? 'bdqn-link-' : 'bdqn-about-' ) . $key, 'parent' => 'bdqn-' . $group, 'title' => esc_html( $item[0] ), 'href' => esc_url( $item[1] ), 'meta' => array( 'target' => '_blank', 'rel' => 'nofollow noopener noreferrer' ) ) ); }
		}
	}

	/**
	 * Resolve an addon menu against its registered capability when available.
	 * @param array $slugs Ordered known menu slugs, current before legacy.
	 * @param string $fallback Fallback slug for frontend requests without admin menus.
	 * @param string $file Admin entry file.
	 * @return string Authorized menu URL, or empty string for a forbidden registered menu.
	 */
	private function addon_url( $slugs, $fallback, $file = 'admin.php' ) {
		global $menu, $submenu;
		$lists = array_merge( array( is_array( $menu ) ? $menu : array() ), is_array( $submenu ) ? array_values( $submenu ) : array() );
		foreach ( $slugs as $slug ) {
			foreach ( $lists as $items ) {
				foreach ( $items as $item ) { if ( ( $item[2] ?? '' ) === $slug ) { return current_user_can( $item[1] ) ? add_query_arg( 'page', $slug, admin_url( $file ) ) : ''; } }
			}
		}
		return add_query_arg( 'page', $fallback, admin_url( $file ) );
	}

	/**
	 * Add one archive and its authorized builder links.
	 * @param \WP_Admin_Bar $bar Toolbar instance.
	 * @param string $id Unique node ID.
	 * @param string $parent Parent node ID.
	 * @param string $label Group label.
	 * @param string $url Archive URL.
	 * @param string $type Registered post type.
	 * @param string $generation Supported generation identifier.
	 * @param string $group Content category.
	 * @param array $settings Effective configuration.
	 * @return void Adds bounded post nodes.
	 */
	private function content_group( $bar, $id, $parent, $label, $url, $type, $generation, $group, $settings ) {
		$bar->add_node( array( 'id' => $id, 'parent' => $parent, 'title' => esc_html( $label ), 'href' => esc_url( $url ) ) );
		$posts = SnippetBuilder::posts( $type, $generation, $group, $settings );
		$buckets = array( 'published' => array(), 'unpublished' => array() );
		foreach ( $posts as $post ) { $buckets[ 'publish' === $post->post_status ? 'published' : 'unpublished' ][] = $post; }
		foreach ( $buckets as $bucket => $items ) {
			if ( ! $items ) { continue; }
			$node_parent = $id;
			if ( 'content' === $group ) {
				$node_parent = $id . '-' . $bucket;
				$bar->add_group( array( 'id' => $node_parent, 'parent' => $id ) );
				$bar->add_node( array( 'id' => $node_parent . '-heading', 'parent' => $node_parent, 'title' => esc_html( 'published' === $bucket ? __( 'Published', 'breakdance-quicknav' ) : __( 'Unpublished', 'breakdance-quicknav' ) ), 'meta' => array( 'class' => 'bdqn-status-heading' ) ) );
			}
			foreach ( $items as $post ) {
				$title = esc_html( '' !== trim( $post->post_title ) ? $post->post_title : __( '(no title)', 'breakdance-quicknav' ) );
				if ( 'publish' !== $post->post_status ) {
					$status = get_post_status_object( $post->post_status );
					$title .= ' <span class="bdqn-status-label">(' . esc_html( $status ? $status->label : $post->post_status ) . ')</span>';
				}
				$bar->add_node( array( 'id' => $id . '-' . $post->ID, 'parent' => $node_parent, 'title' => $title, 'href' => esc_url( SnippetBuilder::edit_url( $post->ID, $generation ) ) ) );
			}
		}
	}

	/**
	 * Retain detected legacy integrations and respect registered target capabilities.
	 * @param \WP_Admin_Bar $bar Toolbar instance.
	 * @param string $parent Parent node.
	 * @return void Adds supported addon menus; addons without a menu remain diagnostic entries.
	 */
	private function addon_nodes( $bar, $parent ) {
		if ( ! SnippetBuilder::full_access() || is_network_admin() || is_user_admin() ) { return; }
		$settings = SnippetSettings::get(); $seen = array(); $group_added = false;
		foreach ( SnippetIntegrations::rows() as $id => $row ) {
			if ( ! SnippetIntegrations::enabled( $id, $settings ) ) { continue; }
			foreach ( $row['links'] as $link ) {
				if ( ! empty( $link['tab'] ) || isset( $seen[ $link['url'] ] ) ) { continue; }
				$seen[ $link['url'] ] = true;
				$addon_parent = $parent;
				if ( empty( $settings['integration_direct'][ $id ] ) ) {
					if ( ! $group_added ) { $bar->add_node( array( 'id' => 'bdqn-addons', 'parent' => $parent, 'title' => esc_html__( 'Add-ons', 'breakdance-quicknav' ) ) ); $group_added = true; }
					$addon_parent = 'bdqn-addons';
				}
				$bar->add_node( array( 'id' => 'bdqn-' . $id, 'parent' => $addon_parent, 'title' => esc_html( $link['label'] ), 'href' => esc_url( $link['url'] ) ) );
				if ( ! empty( $settings['integration_children'] ) ) {
					foreach ( $row['children'] as $child ) {
						if ( isset( $seen[ $child['url'] ] ) ) { continue; }
						$seen[ $child['url'] ] = true;
						$bar->add_node( array( 'id' => 'bdqn-' . $id . '-' . substr( md5( $child['url'] ), 0, 10 ), 'parent' => 'bdqn-' . $id, 'title' => esc_html( $child['label'] ), 'href' => esc_url( $child['url'] ) ) );
					}
				}
			}
		}
	}

	/** Add the same per-form destinations offered by Breakdance's submissions filter. */
	private function form_nodes( $bar, $limit ) {
		if ( ! function_exists( '\Breakdance\Forms\getFormSettings' ) || ! function_exists( '\Breakdance\Forms\Submission\getFormNameFromLatestSubmissionSettings' ) ) { return; }
		global $wpdb;
		// Bound the query and ignore trash; no request parameters are interpolated.
		$forms = $wpdb->get_results( $wpdb->prepare(
			"SELECT DISTINCT p1.meta_value AS formId, p2.meta_value AS postId FROM {$wpdb->postmeta} p1 INNER JOIN {$wpdb->postmeta} p2 ON p1.post_id = p2.post_id INNER JOIN {$wpdb->posts} p ON p.ID = p1.post_id WHERE p1.meta_key = %s AND p2.meta_key = %s AND p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft') ORDER BY p2.meta_value, p1.meta_value LIMIT %d",
			'_breakdance_form_id', '_breakdance_post_id', 'breakdance_form_res', max( 1, min( 100, absint( $limit ) ) )
		), ARRAY_A );
		$seen = array();
		foreach ( $forms as $form ) {
			$post_id = absint( $form['postId'] ?? 0 ); $form_id = absint( $form['formId'] ?? 0 );
			$key = $post_id . '_' . $form_id;
			if ( ! $post_id || ! $form_id || isset( $seen[ $key ] ) ) { continue; }
			$seen[ $key ] = true;
			$settings = \Breakdance\Forms\getFormSettings( $post_id, $form_id );
			$name = $settings ? ( $settings['form']['form_name'] ?? '' ) : \Breakdance\Forms\Submission\getFormNameFromLatestSubmissionSettings( $post_id, $form_id );
			$name = is_scalar( $name ) ? trim( (string) $name ) : '';
			$title = ( $name ?: __( 'Form Submissions', 'breakdance-quicknav' ) ) . ' (' . $post_id . '/' . $form_id . ')';
			$bar->add_node( array( 'id' => 'bdqn-form-' . $key, 'parent' => 'bdqn-form-submissions', 'title' => esc_html( $title ), 'href' => esc_url( add_query_arg( array( 'post_type' => 'breakdance_form_res', 'form_id' => $key ), admin_url( 'edit.php' ) ) ) ) );
			if ( count( $seen ) >= $limit ) { break; }
		}
	}

}

/** @return void Registers one navigation instance unless the full plugin is active. */
$boot = static function () { static $instance; if ( ! $instance && ! defined( 'DDW_BDQN_VERSION' ) && ! class_exists( 'DDW_Breakdance_QuickNav' ) ) { $instance = new SnippetPlugin(); } };
if ( did_action( 'init' ) ) { $boot(); } else { add_action( 'init', $boot, 20 ); }

}
