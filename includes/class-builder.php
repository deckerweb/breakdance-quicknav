<?php
/** Read-only Breakdance integration. @package BreakdanceQuickNav */
namespace Deckerweb\BreakdanceQuickNav;

defined( 'ABSPATH' ) || exit;

/** Resolves supported Breakdance interfaces without altering builder data or files. */
final class Builder {
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
		foreach ( array( 'BREAKDANCE_AI_VERSION' => 'Breakdance AI', 'WPSIX_EXPORTER_VERSION' => 'WPSix Exporter', 'WPSIX_ELEMENTS_VERSION' => 'WPSix Elements', 'IMAGE_REPLACE_VERSION' => 'Image Replacer', 'HSF_VERSION' => 'Headspin Copilot' ) as $constant => $name ) {
			if ( defined( $constant ) && is_scalar( constant( $constant ) ) ) { $out[ $name ] = sanitize_text_field( (string) constant( $constant ) ); }
		}
		foreach ( array( 'Breakdance Migration Mode' => function_exists( '\Breakdance\MigrationMode\saveActivatingUserIp' ), 'Yabe Webfont' => class_exists( '\Yabe\Webfont\Plugin' ), 'Reading Time Calculator' => function_exists( 'bd_reading_time_menu' ) ) as $name => $active ) { if ( $active ) { $out[ $name ] = __( 'Detected', 'breakdance-quicknav' ); } }
		return $out;
	}
}
