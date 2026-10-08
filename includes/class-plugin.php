<?php
/**
 * Host integration. @package BreakdanceQuickNav
 * Adapted from Oxygen QuickNav 2.0.0, © 2025–2026 David Decker – DECKERWEB.
 * GPL-2.0-or-later. https://github.com/deckerweb/oxygen-quicknav/tree/v2.0.0
 */
namespace Deckerweb\BreakdanceQuickNav;

defined( 'ABSPATH' ) || exit;

/** Coordinates toolbar rendering, diagnostics, and website/user settings. */
final class Plugin {
	/** @return void Registers one instance after all active plugins load. */
	public static function boot() {
		static $instance;
		if ( ! $instance ) { $instance = new self(); }
	}

	/** @return void Registers hooks without querying content or making remote requests. */
	public function __construct() {
		add_action( 'init', array( $this, 'translations' ), 5 );
		add_action( 'init', array( '\Deckerweb\BreakdanceQuickNav\Updates', 'register' ), 20 );
		add_action( 'admin_bar_menu', array( $this, 'toolbar' ), 999 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 100 );
		add_action( 'admin_notices', array( $this, 'builder_notice' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DDW_BDQN_FILE ), array( $this, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
		add_filter( 'debug_information', array( $this, 'diagnostics' ) );
		add_action( 'show_user_profile', array( $this, 'profile' ) );
		add_action( 'edit_user_profile', array( $this, 'profile' ) );
		add_action( 'personal_options_update', array( $this, 'save_profile' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile' ) );
	}

	/** @return void Makes packaged host translations available from init onward. */
	public function translations() {
		load_plugin_textdomain( 'breakdance-quicknav', false, dirname( plugin_basename( DDW_BDQN_FILE ) ) . '/languages' );
	}

	/** @return string Website-scoped settings URL. */
	public static function url() {
		return admin_url( ( Builder::active() ? 'admin.php' : 'options-general.php' ) . '?page=breakdance-quicknav' );
	}

	/** @return void Adds a native settings page even when Breakdance is inactive. */
	public function admin_menu() {
		if ( Builder::active() ) {
			add_submenu_page( 'breakdance', 'Breakdance QuickNav', 'QuickNav', 'manage_options', 'breakdance-quicknav', array( $this, 'page' ) );
		} else {
			add_options_page( 'Breakdance QuickNav', 'Breakdance QuickNav', 'manage_options', 'breakdance-quicknav', array( $this, 'page' ) );
		}
	}

	/** @return void Explains inactive navigation within the website's native plugin screen. */
	public function builder_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id || is_network_admin() || ! current_user_can( 'activate_plugins' ) || Builder::active() ) { return; }
		echo '<div class="notice notice-info"><p><strong>Breakdance QuickNav:</strong> ' . esc_html__( 'No supported Breakdance builder (2.x or 3.x) is active. QuickNav settings remain available; toolbar navigation resumes when a supported builder is active.', 'breakdance-quicknav' );
		if ( current_user_can( 'manage_options' ) ) { echo ' <a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'breakdance-quicknav' ) . '</a>'; }
		echo '</p></div>';
	}

	/** @return void Registers one sanitized website option with the WordPress Settings API. */
	public function register_settings() {
		register_setting( 'ddw_bdqn', Settings::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( $this, 'save_settings' ), 'default' => Settings::defaults() ) );
	}


	/**
	 * Preserve stored defaults for fields locked by deployment constants.
	 * @param mixed $input Submitted website settings.
	 * @return array Validated website settings with existing locked values retained.
	 */
	public function save_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$old = get_option( Settings::OPTION, Settings::defaults() );
		$old = is_array( $old ) ? array_merge( Settings::defaults(), $old ) : Settings::defaults();
		foreach ( array( 'integrations', 'integration_children' ) as $key ) { if ( ! array_key_exists( $key, $input ) ) { $input[ $key ] = $old[ $key ]; } }
		foreach ( Settings::constant_map() as $key => $constant ) { if ( defined( $constant ) ) { $input[ $key ] = $old[ $key ]; } }
		return Settings::sanitize( $input );
	}

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

	/**
	 * Load local UI assets only on the host page or an eligible visible toolbar.
	 * @param string $hook Optional WordPress admin screen hook suffix.
	 * @return void Enqueues scoped CSS and settings-only accessible dialog JS.
	 */
	public function assets( $hook = '' ) {
		$page = in_array( $hook, array( 'settings_page_breakdance-quicknav', 'breakdance_page_breakdance-quicknav' ), true );
		if ( ! $page && ( ! $this->visible( Settings::get() ) || ! Builder::active() ) ) { return; }
		wp_enqueue_style( 'ddw-bdqn', plugins_url( 'assets/quicknav.css', DDW_BDQN_FILE ), array(), DDW_BDQN_VERSION );
		if ( $page ) { wp_enqueue_script( 'ddw-bdqn-dialog', plugins_url( 'assets/dialog.js', DDW_BDQN_FILE ), array(), DDW_BDQN_VERSION, true ); }
	}

	/**
	 * Prepend the settings action in the website plugin list.
	 * @param array $links Existing action links.
	 * @return array Settings followed by existing actions when authorized.
	 */
	public function action_links( $links ) {
		if ( ! is_network_admin() && current_user_can( 'manage_options' ) ) { array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'breakdance-quicknav' ) . '</a>' ); }
		return $links;
	}

	/**
	 * Add neutral documentation and support links without personal URL parameters.
	 * @param array $links Existing metadata links.
	 * @param string $file Listed plugin basename.
	 * @return array Unchanged links or the augmented host row.
	 */
	public function row_meta( $links, $file ) {
		if ( plugin_basename( DDW_BDQN_FILE ) !== $file ) { return $links; }
		$links[] = '<a href="https://github.com/deckerweb/breakdance-quicknav" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'breakdance-quicknav' ) . '</a>';
		$links[] = '<a href="https://ko-fi.com/deckerweb" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support this plugin', 'breakdance-quicknav' ) . '</a>';
		$links[] = '<a href="https://eepurl.com/gbAUUn" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Newsletter', 'breakdance-quicknav' ) . '</a>';
		return $links;
	}

	/**
	 * Render authorized Breakdance content and settings while preserving native editor behavior.
	 * @param \WP_Admin_Bar $bar WordPress toolbar instance.
	 * @return void Adds host-scoped navigation when the builder and user are eligible.
	 */
	public function toolbar( $bar ) {
		$settings = Settings::get();
		if ( ! Builder::active() || class_exists( 'Breakdance_Navigator' ) || class_exists( 'DDW_Breakdance_QuickNav' ) || ! $this->visible( $settings ) ) { return; }
		$title = esc_html( $settings['name'] );
		if ( 'none' !== $settings['icon'] ) {
			$icon = plugins_url( 'images/breakdance-icon.png', DDW_BDQN_FILE );
			if ( 'yellow' !== $settings['icon'] ) {
				// Resolve the actual builder directory; renamed plugin folders remain valid.
				$reflection = new \ReflectionFunction( '\Breakdance\Admin\get_builder_loader_url' );
				$path = dirname( $reflection->getFileName(), 3 ) . '/builder/dist/favicon-dark.svg';
				if ( is_file( $path ) ) { $icon = plugins_url( 'builder/dist/favicon-dark.svg', dirname( $reflection->getFileName(), 3 ) . '/plugin.php' ); }
			}
			$title = '<img class="bdqn-icon" src="' . esc_url( $icon ) . '" alt="" width="16" height="16">' . $title;
		}
		$root = 'ddw-breakdance-quicknav';
		$bar->add_node( array( 'id' => $root, 'title' => $title, 'href' => current_user_can( 'manage_options' ) ? self::url() : '#', 'meta' => array( 'menu_title' => 'Breakdance QuickNav' ) ) );
		if ( in_array( 'content', $settings['groups'], true ) ) {
			foreach ( $settings['post_types'] as $type ) {
				$object = get_post_type_object( $type );
				if ( ! $object || ! current_user_can( $object->cap->edit_posts ) || ! function_exists( '\Breakdance\Permissions\isPostTypeAllowed' ) || ! \Breakdance\Permissions\isPostTypeAllowed( $type ) ) { continue; }
				$this->content_group( $bar, 'page' === $type ? 'bdqn-pages' : 'bdqn-content-' . $type, $root, $object->labels->name, admin_url( 'edit.php?post_type=' . $type ), $type, 'breakdance', 'content', $settings );
			}
		}
		foreach ( Builder::groups( 'breakdance' ) as $group => $info ) {
			$object = get_post_type_object( $info[1] );
			if ( ! Builder::full_access() || ! $object || ! current_user_can( $object->cap->edit_posts ) || ! in_array( $group, $settings['groups'], true ) ) { continue; }
			$this->content_group( $bar, 'bdqn-' . $group, $root, $info[0], $info[2], $info[1], 'breakdance', $group, $settings );
		}
		if ( in_array( 'settings', $settings['groups'], true ) ) {
			if ( post_type_exists( 'breakdance_form_res' ) && function_exists( '\Breakdance\Forms\Submission\canViewSubmissions' ) && \Breakdance\Forms\Submission\canViewSubmissions() ) {
				$bar->add_node( array( 'id' => 'bdqn-form-submissions', 'parent' => $root, 'title' => esc_html__( 'Form Submissions', 'breakdance-quicknav' ), 'href' => admin_url( 'edit.php?post_type=breakdance_form_res' ) ) );
				$this->form_nodes( $bar, $settings['limit'] );
			}
			if ( current_user_can( 'manage_options' ) && Builder::full_access() ) {
				$url = Builder::styles_url();
				if ( $url ) { $bar->add_node( array( 'id' => 'bdqn-edit-global-styles', 'parent' => $root, 'title' => esc_html__( 'Edit Global Styles', 'breakdance-quicknav' ), 'href' => esc_url( $url ), 'meta' => array( 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ) ); }
				if ( function_exists( '\Breakdance\DesignLibrary\hideDesignLibrary' ) && ! \Breakdance\DesignLibrary\hideDesignLibrary() ) { $bar->add_node( array( 'id' => 'bdqn-design-library', 'parent' => $root, 'title' => esc_html__( 'Design Library', 'breakdance-quicknav' ), 'href' => admin_url( 'admin.php?page=breakdance_design_library' ) ) ); }
				$bar->add_node( array( 'id' => 'bdqn-settings', 'parent' => $root, 'title' => esc_html__( 'Breakdance Settings', 'breakdance-quicknav' ), 'href' => Builder::settings_url( 'breakdance' ) ) );
				foreach ( Builder::tabs( 'breakdance' ) as $tab => $label ) { if ( in_array( $tab, array( 'ai', 'migration-mode' ), true ) && ! Integrations::enabled( $tab, $settings ) ) { continue; } $bar->add_node( array( 'id' => 'bdqn-settings-' . $tab, 'parent' => 'bdqn-settings', 'title' => esc_html( $label ), 'href' => esc_url( Builder::settings_url( 'breakdance', $tab ) ) ) ); }
			}
		}
		if ( in_array( 'addons', $settings['groups'], true ) ) { $this->addon_nodes( $bar, $root ); }
		if ( current_user_can( 'manage_options' ) ) { $bar->add_node( array( 'id' => 'bdqn-own-settings', 'parent' => $root, 'title' => esc_html__( 'QuickNav Settings', 'breakdance-quicknav' ), 'href' => self::url() ) ); }
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
		$posts = Builder::posts( $type, $generation, $group, $settings );
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
				$bar->add_node( array( 'id' => $id . '-' . $post->ID, 'parent' => $node_parent, 'title' => $title, 'href' => esc_url( Builder::edit_url( $post->ID, $generation ) ) ) );
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
		if ( ! Builder::full_access() || is_network_admin() || is_user_admin() ) { return; }
		$settings = Settings::get(); $seen = array();
		foreach ( Integrations::rows() as $id => $row ) {
			if ( ! Integrations::enabled( $id, $settings ) ) { continue; }
			foreach ( $row['links'] as $link ) {
				if ( ! empty( $link['tab'] ) || isset( $seen[ $link['url'] ] ) ) { continue; }
				$seen[ $link['url'] ] = true;
				$bar->add_node( array( 'id' => 'bdqn-' . $id, 'parent' => $parent, 'title' => esc_html( $link['label'] ), 'href' => esc_url( $link['url'] ) ) );
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

	/**
	 * Publish dataminimal builder and component versions in Site Health.
	 * @param array $info Existing debug sections.
	 * @return array Debug sections with no user IDs, keys, licenses, or content.
	 */
	public function diagnostics( $info ) {
		$fields = array( 'plugin' => array( 'label' => __( 'Plugin version', 'breakdance-quicknav' ), 'value' => DDW_BDQN_VERSION ), 'builders' => array( 'label' => __( 'Active builder', 'breakdance-quicknav' ), 'value' => implode( ', ', Builder::active() ) ?: __( 'None', 'breakdance-quicknav' ) ) );
		foreach ( Builder::active() as $generation ) { $fields[ $generation ] = array( 'label' => 'Breakdance', 'value' => __BREAKDANCE_VERSION ); }
		foreach ( Builder::addons() as $name => $version ) { $fields[ sanitize_key( $name ) ] = array( 'label' => $name, 'value' => $version ); }
		$runtime = $GLOBALS['deckerweb_library_runtime_v1'] ?? null;
		$fields['library'] = array( 'label' => 'deckerweb Library', 'value' => is_object( $runtime ) ? get_class( $runtime )::VERSION : __( 'Not loaded', 'breakdance-quicknav' ) );
		$fields['library-bundled'] = array( 'label' => __( 'Bundled Library', 'breakdance-quicknav' ), 'value' => '0.8.1' );
		$fields['updater'] = array( 'label' => 'deckerweb Updater', 'value' => Updates::$error ? __( 'Not loaded', 'breakdance-quicknav' ) : '2.1.0' );
		$settings = Settings::get( false );
		$fields['display'] = array( 'label' => __( 'Website display', 'breakdance-quicknav' ), 'value' => sprintf( __( 'Admin Dashboard: %1$s · Website: %2$s · Links & About: %3$s', 'breakdance-quicknav' ), $settings['backend'] ? __( 'Enabled', 'breakdance-quicknav' ) : __( 'Disabled', 'breakdance-quicknav' ), $settings['frontend'] ? __( 'Enabled', 'breakdance-quicknav' ) : __( 'Disabled', 'breakdance-quicknav' ), $settings['footer'] ? __( 'Enabled', 'breakdance-quicknav' ) : __( 'Disabled', 'breakdance-quicknav' ) ) );
		$fields['limit'] = array( 'label' => __( 'Items per group', 'breakdance-quicknav' ), 'value' => (string) $settings['limit'] );
		$fields['overrides'] = array( 'label' => __( 'Configuration overrides', 'breakdance-quicknav' ), 'value' => implode( ', ', array_filter( Settings::constant_map(), 'defined' ) ) ?: __( 'None', 'breakdance-quicknav' ) );
		$info['breakdance-quicknav'] = array( 'label' => 'Breakdance QuickNav', 'fields' => $fields );
		return $info;
	}

	/** @return void Renders the authorized settings page with local structured history. */
	public function page() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You do not have permission to manage these settings.', 'breakdance-quicknav' ) ); }
		$s = Settings::get( false );
		echo '<div class="wrap bdqn-settings"><header class="bdqn-header"><img src="' . esc_url( plugins_url( 'assets/brand/icon.svg', DDW_BDQN_FILE ) ) . '" width="52" height="52" alt=""><div><h1>Breakdance QuickNav</h1><p>' . esc_html__( 'Your shortcuts. Breakdance 2 & 3.', 'breakdance-quicknav' ) . '</p></div></header><hr class="wp-header-end">';
		// WordPress already renders Settings API notices for options-general.php pages.
		global $parent_file;
		if ( 'options-general.php' !== $parent_file ) { settings_errors(); }
		if ( ! Builder::active() ) { echo '<div class="notice notice-info inline"><p>' . esc_html__( 'No supported Breakdance builder (2.x or 3.x) is active. QuickNav settings remain available; toolbar navigation resumes when a supported builder is active.', 'breakdance-quicknav' ) . '</p></div>'; }
		if ( version_compare( PHP_VERSION, '8.1', '<' ) || Updates::$error ) { echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Navigation remains available. The bundled Library needs PHP 8.0; the updater integration needs PHP 8.1 and a compatible deckerweb Updater. Update manually while a component is unavailable.', 'breakdance-quicknav' ) . '</p></div>'; }
		echo '<form method="post" action="options.php">';
		settings_fields( 'ddw_bdqn' );
		echo '<h2>' . esc_html__( 'Display & Content', 'breakdance-quicknav' ) . '</h2><p>' . esc_html__( 'These defaults apply to this website. Personal toolbar preferences are available in your user profile.', 'breakdance-quicknav' ) . '</p><table class="form-table" role="presentation">';
		$this->field( 'name', __( 'Toolbar label', 'breakdance-quicknav' ), 'text', $s );
		$this->field( 'icon', __( 'Icon', 'breakdance-quicknav' ), array( 'auto' => __( 'Breakdance icon', 'breakdance-quicknav' ), 'yellow' => __( 'Original yellow icon', 'breakdance-quicknav' ), 'none' => __( 'No icon', 'breakdance-quicknav' ) ), $s );
		$this->field( 'limit', __( 'Items per group', 'breakdance-quicknav' ), 'number', $s );
		$this->field( 'orderby', __( 'Sort by', 'breakdance-quicknav' ), array( 'modified' => __( 'Recently modified', 'breakdance-quicknav' ), 'title' => __( 'Title', 'breakdance-quicknav' ) ), $s );
		foreach ( array( 'backend' => __( 'Show in the Admin Dashboard', 'breakdance-quicknav' ), 'frontend' => __( 'Show on the website', 'breakdance-quicknav' ), 'footer' => __( 'Show Links & About', 'breakdance-quicknav' ), 'drafts' => __( 'Include unpublished content you can edit', 'breakdance-quicknav' ) ) as $key => $label ) { $this->field( $key, $label, 'checkbox', $s ); }
		echo '<tr><th scope="row" id="bdqn-content-types-label">' . esc_html__( 'Content types', 'breakdance-quicknav' ) . '</th><td><fieldset class="bdqn-choices" aria-labelledby="bdqn-content-types-label"><input type="hidden" name="ddw_bdqn_settings[post_types][]" value="">';
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type => $object ) { $this->choice( 'post_types', $type, $object->labels->name, $s['post_types'] ); }
		echo '</fieldset></td></tr><tr><th scope="row" id="bdqn-menu-groups-label">' . esc_html__( 'Menu groups', 'breakdance-quicknav' ) . '</th><td><fieldset class="bdqn-choices" aria-labelledby="bdqn-menu-groups-label"><input type="hidden" name="ddw_bdqn_settings[groups][]" value="">';
		foreach ( array( 'content' => __( 'Content', 'breakdance-quicknav' ), 'templates' => __( 'Templates', 'breakdance-quicknav' ), 'headers' => __( 'Headers', 'breakdance-quicknav' ), 'footers' => __( 'Footers', 'breakdance-quicknav' ), 'global-blocks' => __( 'Global Blocks', 'breakdance-quicknav' ), 'popups' => __( 'Popups', 'breakdance-quicknav' ), 'settings' => __( 'Breakdance Settings', 'breakdance-quicknav' ), 'addons' => __( 'Add-ons', 'breakdance-quicknav' ) ) as $key => $label ) { $this->choice( 'groups', $key, $label, $s['groups'] ); }
		echo '<p class="description">' . esc_html__( 'Only groups available in the active builder are displayed.', 'breakdance-quicknav' ) . '</p></fieldset></td></tr></table><h2>' . esc_html__( 'Access', 'breakdance-quicknav' ) . '</h2><table class="form-table" role="presentation">';
		$this->field( 'capability', __( 'Required toolbar capability', 'breakdance-quicknav' ), 'text', $s );
		$this->field( 'users', __( 'Restrict to user IDs', 'breakdance-quicknav' ), 'text', $s );
		echo '</table><p>' . esc_html__( 'Leave user IDs empty to allow all eligible users. Separate IDs with commas. WordPress and Breakdance permissions always apply to each destination.', 'breakdance-quicknav' ) . '</p><h2>' . esc_html__( 'Data', 'breakdance-quicknav' ) . '</h2><table class="form-table" role="presentation">';
		$this->field( 'delete_data', __( 'Delete QuickNav settings when uninstalling', 'breakdance-quicknav' ), 'checkbox', $s );
		echo '</table><p>' . esc_html__( 'Disabled by default. When enabled, uninstall removes this website’s QuickNav settings and personal QuickNav preferences. Breakdance content and other plugins’ data are always retained. Network uninstall applies each website’s own choice.', 'breakdance-quicknav' ) . '</p>';
		$this->integration_settings( $s );
		submit_button();
		echo '</form><h2>' . esc_html__( 'Detected components', 'breakdance-quicknav' ) . '</h2><dl class="bdqn-diagnostics">';
		foreach ( $this->diagnostics( array() )['breakdance-quicknav']['fields'] as $field ) { echo '<dt>' . esc_html( $field['label'] ) . '</dt><dd>' . esc_html( $field['value'] ) . '</dd>'; }
		echo '</dl><footer class="bdqn-footer" aria-label="' . esc_attr__( 'Plugin information', 'breakdance-quicknav' ) . '"><div><strong>Breakdance QuickNav</strong> <span>' . esc_html__( 'Version', 'breakdance-quicknav' ) . ' ' . esc_html( DDW_BDQN_VERSION ) . '</span> · <button type="button" class="button-link" data-bdqn-open="bdqn-history">' . esc_html__( 'Changelog', 'breakdance-quicknav' ) . '</button> · <a href="https://github.com/deckerweb/breakdance-quicknav">' . esc_html__( 'Documentation', 'breakdance-quicknav' ) . '</a><p>' . esc_html__( 'Your shortcuts. Breakdance 2 & 3.', 'breakdance-quicknav' ) . '</p></div><div><span>© 2025–2026 <a href="https://github.com/deckerweb" target="_blank" rel="noopener noreferrer">David Decker – DECKERWEB</a></span><a href="https://github.com/deckerweb/breakdance-quicknav" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Plugin website', 'breakdance-quicknav' ) . '</a></div></footer>';
		echo '<dialog id="bdqn-history" aria-labelledby="bdqn-history-title"><button type="button" class="button bdqn-close" data-bdqn-close>' . esc_html__( 'Close', 'breakdance-quicknav' ) . '</button><h2 id="bdqn-history-title">' . esc_html__( 'Changelog', 'breakdance-quicknav' ) . '</h2>';
		$history = require DDW_BDQN_DIR . '/includes/plugin-history.php';
		echo $history( 0 === strpos( determine_locale(), 'de' ) ); // All source values escaped in the renderer.
		echo '</dialog></div>';
	}

	/** @param array $settings Website settings. @return void Displays addon availability and site-scoped navigation switches. */
	private function integration_settings( $settings ) {
		echo '<h2 id="bdqn-integrations">' . esc_html__( 'Third-party integrations', 'breakdance-quicknav' ) . '</h2><p>' . esc_html__( 'Choose which addon shortcuts QuickNav displays on this website. These switches do not activate, deactivate or configure the addons themselves. Missing addons never produce toolbar links.', 'breakdance-quicknav' ) . '</p><table class="form-table" role="presentation">';
		$this->field( 'integration_children', __( 'Show addon submenus', 'breakdance-quicknav' ), 'checkbox', $settings );
		echo '</table><div class="bdqn-integration-list">';
		foreach ( Integrations::rows() as $id => $row ) {
			$control = 'bdqn-integration-' . $id;
			echo '<div class="bdqn-integration"><div><strong>' . esc_html( $row['label'] ) . '</strong>';
			if ( $row['version'] ) { echo ' <span>' . esc_html( $row['version'] ) . '</span>'; }
			echo '<p class="description">' . esc_html( Integrations::status_label( $row['status'] ) ) . '</p>';
			if ( ! $row['pending'] && 'no-menu' !== $row['status'] ) {
				echo '<input type="hidden" name="ddw_bdqn_settings[integrations][' . esc_attr( $id ) . ']" value="0"><label for="' . esc_attr( $control ) . '"><input type="checkbox" id="' . esc_attr( $control ) . '" name="ddw_bdqn_settings[integrations][' . esc_attr( $id ) . ']" value="1"' . checked( Integrations::enabled( $id, $settings ), true, false ) . '> ' . esc_html__( 'Show QuickNav links for this addon', 'breakdance-quicknav' ) . '</label>';
			}
			echo '</div><div>';
			if ( $row['legacy'] ) { echo '<span class="description">' . esc_html__( 'Existing integration retained', 'breakdance-quicknav' ) . '</span>'; }
			if ( $row['links'] ) { echo '<p><a href="' . esc_url( $row['links'][0]['url'] ) . '">' . esc_html__( 'Open addon admin page', 'breakdance-quicknav' ) . '</a></p>'; }
			echo '</div></div>';
		}
		echo '</div>';
	}

	/**
	 * Render an accessible native setting control with visible constant overrides.
	 * @param string $key Setting key.
	 * @param string $label Translated field label.
	 * @param string|array $kind Control type or choices.
	 * @param array $settings Effective website settings.
	 * @return void Outputs an escaped form row.
	 */
	private function field( $key, $label, $kind, $settings ) {
		$constant = Settings::constant_map()[ $key ] ?? '';
		$locked = $constant && defined( $constant );
		$id = 'bdqn-' . $key;
		$name = Settings::OPTION . '[' . $key . ']';
		$value = 'users' === $key ? implode( ', ', $settings[ $key ] ) : $settings[ $key ];
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( is_array( $kind ) ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . disabled( $locked, true, false ) . '>';
			foreach ( $kind as $option => $text ) { echo '<option value="' . esc_attr( $option ) . '"' . selected( $value, $option, false ) . '>' . esc_html( $text ) . '</option>'; }
			echo '</select>';
		} elseif ( 'checkbox' === $kind ) {
			echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( $value, true, false ) . disabled( $locked, true, false ) . '>';
		} else { echo '<input class="regular-text" type="' . esc_attr( $kind ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( 'number' === $kind ? ' min="1" max="100"' : '' ) . disabled( $locked, true, false ) . '>'; }
		if ( $locked ) { echo '<p class="description">' . esc_html__( 'Set by configuration:', 'breakdance-quicknav' ) . ' <code>' . esc_html( $constant ) . '</code></p>'; }
		echo '</td></tr>';
	}

	/**
	 * Render a selection in a multi-choice setting.
	 * @param string $key Setting key.
	 * @param string $value Choice value.
	 * @param string $label Choice label.
	 * @param array $selected Selected choice values.
	 * @return void Outputs one escaped checkbox label.
	 */
	private function choice( $key, $value, $label, $selected ) {
		echo '<label class="bdqn-choice"><input type="checkbox" name="' . esc_attr( Settings::OPTION . '[' . $key . '][]' ) . '" value="' . esc_attr( $value ) . '"' . checked( in_array( $value, $selected, true ), true, false ) . '><span>' . esc_html( $label ) . '</span></label>';
	}

	/**
	 * Render personal, website-scoped toolbar preferences on a user profile.
	 * @param \WP_User $user Profile user.
	 * @return void Outputs controls; personal settings cannot expand site permissions.
	 */
	public function profile( $user ) {
		if ( is_network_admin() || is_user_admin() || ! current_user_can( 'edit_user', $user->ID ) ) { return; }
		$prefs = get_user_option( Settings::USER_OPTION, $user->ID );
		$prefs = is_array( $prefs ) ? $prefs : array();
		echo '<h2>Breakdance QuickNav</h2><p>' . esc_html__( 'Personal preferences for this website. Website permissions and configuration overrides still apply.', 'breakdance-quicknav' ) . '</p>';
		wp_nonce_field( 'bdqn-profile-' . $user->ID, 'bdqn_profile_nonce' );
		echo '<table class="form-table" role="presentation">';
		foreach ( array( 'backend' => __( 'Admin Dashboard toolbar', 'breakdance-quicknav' ), 'frontend' => __( 'Website toolbar', 'breakdance-quicknav' ) ) as $key => $label ) {
			echo '<tr><th><label for="bdqn-personal-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><select id="bdqn-personal-' . esc_attr( $key ) . '" name="bdqn_preferences[' . esc_attr( $key ) . ']"><option value="inherit">' . esc_html__( 'Use website default', 'breakdance-quicknav' ) . '</option><option value="hide"' . selected( $prefs[ $key ] ?? '', 'hide', false ) . '>' . esc_html__( 'Hide for me', 'breakdance-quicknav' ) . '</option></select></td></tr>';
		}
		echo '<tr><th><label for="bdqn-personal-limit">' . esc_html__( 'Items per group', 'breakdance-quicknav' ) . '</label></th><td><input type="number" min="0" max="100" id="bdqn-personal-limit" name="bdqn_preferences[limit]" value="' . esc_attr( $prefs['limit'] ?? 0 ) . '"><p class="description">' . esc_html__( '0 uses the website default.', 'breakdance-quicknav' ) . '</p></td></tr></table>';
	}

	/**
	 * Save authorized, nonce-verified personal preferences for this website only.
	 * @param int $id Profile user ID.
	 * @return void Saves validated values or leaves existing preferences untouched.
	 */
	public function save_profile( $id ) {
		if ( is_network_admin() || is_user_admin() || ! current_user_can( 'edit_user', $id ) || ! isset( $_POST['bdqn_profile_nonce'] ) || ! is_string( $_POST['bdqn_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bdqn_profile_nonce'] ) ), 'bdqn-profile-' . $id ) ) { return; }
		$input = isset( $_POST['bdqn_preferences'] ) && is_array( $_POST['bdqn_preferences'] ) ? wp_unslash( $_POST['bdqn_preferences'] ) : array();
		$prefs = array( 'limit' => is_scalar( $input['limit'] ?? null ) ? min( 100, absint( $input['limit'] ) ) : 0 );
		foreach ( array( 'backend', 'frontend' ) as $key ) { $prefs[ $key ] = 'hide' === ( $input[ $key ] ?? '' ) ? 'hide' : 'inherit'; }
		update_user_option( $id, Settings::USER_OPTION, $prefs, false );
	}
}
