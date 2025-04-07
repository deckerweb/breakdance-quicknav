<?php
/*
Forked from "Breakdance Navigator" by Peter Kulcsár
License: GPL v2 or later
GitHub Repository: https://github.com/beamkiller/breakdance-navigator
Original Copyright: © 2024, Peter Kulcsár
*/
 
/*
Plugin Name:        Breakdance QuickNav
Plugin URI:         https://github.com/deckerweb/breakdance-quicknav
Description:        Adds a quick-access navigator (aka QuickNav) to the WordPress Admin Bar (Toolbar). It allows easy access to Breakdance Templates, Headers, Footers, Global Blocks, Popups, and Pages edited with Breakdance, along with some other essential settings.
Project:            Code Snippet: DDW Breakdance QuickNav
Version:            1.1.0
Author:             David Decker – DECKERWEB
Author URI:         https://deckerweb.de/
Text Domain:        breakdance-quicknav
Domain Path:        /languages/
License:            GPL-2.0-or-later 
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
Requires WP:        6.7
Requires PHP:       7.4
GitHub Plugin URI:  https://github.com/deckerweb/breakdance-quicknav
Primary Branch:     main

Original Copyright: © 2024, Peter Kulcsár
Copyright:          © 2025, David Decker – DECKERWEB

TESTED WITH:
Product			Versions
--------------------------------------------------------------------------------------------------------------
PHP 			8.0, 8.3
WordPress		6.7.2 ... 6.8 Beta
Breakdance Pro	2.3.0 ... 2.4.0 Beta
--------------------------------------------------------------------------------------------------------------

VERSION HISTORY:
Date        Version     Description
--------------------------------------------------------------------------------------------------------------
2025-04-??	1.1.0       New: Adjust the number of shown templates via constant (default: up to 20)
                        New: Show Admin Bar also in Block Editor full screen mode
                        New: Add info to Site Health Debug, useful for our constants for custom tweaking
2025-03-08	1.0.0	    Initial release
2025-03-07	0.5.0       Internal test version
2025-03-07	0.0.0	    Development start
--------------------------------------------------------------------------------------------------------------
*/

/** Prevent direct access */
if ( ! defined( 'ABSPATH' ) ) exit;  // Exit if accessed directly.

if ( ! class_exists( 'DDW_Breakdance_QuickNav' ) ) :

class DDW_Breakdance_QuickNav {

    /** Class constants & variables */
    private const VERSION = '1.1.0';
    private const NUMBER_OF_TEMPLATES = 20;

    /**
     * Constructor
     */
    public function __construct() {           
        add_action( 'admin_bar_menu',              array( $this, 'add_admin_bar_menu' ), 999 );
        add_action( 'admin_enqueue_scripts',       array( $this, 'enqueue_admin_bar_styles' ) );  // for Admin
        add_action( 'wp_enqueue_scripts',          array( $this, 'enqueue_admin_bar_styles' ) );  // for front-end
        add_action( 'enqueue_block_editor_assets', array( $this, 'adminbar_block_editor_fullscreen' ) );  // for Block Editor
        add_filter( 'debug_information',           array( $this, 'site_health_debug_info' ), 9 );
    }

    /**
     * Get specific Admin Color scheme colors we need. Covers all 9 default
     *	 color schemes coming with a default WordPress install.
     *   (helper function)
     */
    private function get_scheme_colors() {
        
        $scheme_colors = array(
            'fresh' => array(
                'bg'    => '#1d2327',
                'base'  => 'rgba(240,246,252,.6)',
                'hover' => '#72aee6',
            ),
            'light' => array(
                'bg'    => '#e5e5e5',
                'base'  => '#999',
                'hover' => '#04a4cc',
            ),
            'modern' => array(
                'bg'    => '#1e1e1e',
                'base'  => '#f3f1f1',
                'hover' => '#33f078',
            ),
            'blue' => array(
                'bg'    => '#52accc',
                'base'  => '#e5f8ff',
                'hover' => '#fff',
            ),
            'coffee' => array(
                'bg'    => '#59524c',
                'base'  => 'hsl(27.6923076923,7%,95%)',
                'hover' => '#c7a589',
            ),
            'ectoplasm' => array(
                'bg'    => '#523f6d',
                'base'  => '#ece6f6',
                'hover' => '#a3b745',
            ),
            'midnight' => array(
                'bg'    => '#363b3f',
                'base'  => 'hsl(206.6666666667,7%,95%)',
                'hover' => '#e14d43',
            ),
            'ocean' => array(
                'bg'    => '#738e96',
                'base'  => '#f2fcff',
                'hover' => '#9ebaa0',
            ),
            'sunrise' => array(
                'bg'    => '#cf4944',
                'base'  => 'hsl(2.1582733813,7%,95%)',
                'hover' => 'rgb(247.3869565217,227.0108695652,211.1130434783)',
            ),
        );
        
        /** No filter currently b/c of sanitizing issues with the above CSS values */
        //$scheme_colors = (array) apply_filters( 'ddw/quicknav/csn_scheme_colors', $scheme_colors );
        
        return $scheme_colors;
    }
    
    /**
     * Enqueue custom styles for the admin bar.
     */
    public function enqueue_admin_bar_styles() {
        
        /**
         * Depending on user color scheme get proper base and hover color values for the main item (svg) icon.
         */
        $user_color_scheme = get_user_option( 'admin_color' );
        $user_color_scheme = ( is_admin() || is_network_admin() ) ? $user_color_scheme : 'fresh';
        $admin_scheme      = $this->get_scheme_colors();
        
        $base_color  = $admin_scheme[ $user_color_scheme ][ 'base' ];
        $hover_color = $admin_scheme[ $user_color_scheme ][ 'hover' ];
        
        $inline_css = sprintf(
            '
            /* for the separator */
            #wp-admin-bar-ddw-breakdance-quicknav > .ab-sub-wrapper #wp-admin-bar-bdqn-settings {
                border-bottom: 1px dashed rgba(255, 255, 255, 0.33);
                padding-bottom: 5px;
            }
            
            /* for icons */
            #wpadminbar .has-icon .icon-svg svg {
                display: inline-block;
                margin-bottom: 3px;
                vertical-align: middle;
                width: 16px;
                height: 16px;
            }
            '
        );
        
        if ( is_admin_bar_showing() ) {
            wp_add_inline_style( 'admin-bar', $inline_css );
        }
    }

    /**
     * Number of templates/pages to query for. Can be tweaked via constant.
     *   (Helper function)
     *
     * @return int Number of templates.
     */
    private function number_of_templates() {
            
        $number_of_templates = defined( 'BDQN_NUMBER_TEMPLATES' ) ? (int) BDQN_NUMBER_TEMPLATES : self::NUMBER_OF_TEMPLATES;
        
        return $number_of_templates;
    }
    
    /**
     * Get items of a Breakdance template type. (Helper function)
     *
     * @uses get_posts()
     *
     * @param string $post_type Slug of post type to query for.
     */
    private function get_breakdance_template_type( $post_type ) {
        
        /** only BD-edited pages have the key: '_breakdance_data' */
        $pages_meta_query = ( 'page' === $post_type ) ? [ 'key' => '_breakdance_data', 'compare' => 'EXISTS' ] : [];
            
        $args = array(
            'post_type'      => sanitize_key( $post_type ),
            'posts_per_page' => absint( $this->number_of_templates() ),
            'post_status'    => 'publish',
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => [ $pages_meta_query ],  // optional
        );
        
        apply_filters( 'ddw/quicknav/bd_get_template_type', $args, $post_type );
        
        return get_posts( $args );
    }
    
    /**
     * Adds the main Breakdance menu and its submenus to the Admin Bar.
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
     */
    public function add_admin_bar_menu( $wp_admin_bar ) {
        
        $enabled_users = defined( 'BDQN_ENABLED_USERS' ) ? (array) BDQN_ENABLED_USERS : [];
        
        /** Optional: let only defined user IDs access the plugin */
        if ( defined( 'BDQN_ENABLED_USERS' ) && ! in_array( get_current_user_id(), $enabled_users ) ) {
            return;
        }
        
        /** Don't do anything if Breakdance Builder plugin is NOT active */
        if ( ! defined( '__BREAKDANCE_VERSION' ) ) return;
        
        $bdqn_permission = ( defined( 'BDQN_VIEW_CAPABILITY' ) ) ? BDQN_VIEW_CAPABILITY : 'activate_plugins';
        
        if ( ! current_user_can( sanitize_key( $bdqn_permission ) ) ) {
            return;
        }
        
        $bdqn_name = ( defined( 'BDQN_NAME_IN_ADMINBAR' ) ) ? esc_html( BDQN_NAME_IN_ADMINBAR ) : esc_html__( 'BD', 'breakdance-quicknav' );
        
        /**
         * Add the parent menu item with an icon (main node)
         */
        $bd_builder_icon  = 'breakdance/builder/dist/favicon-dark.svg';
        $bd_packaged_icon = plugin_dir_url( __FILE__ ) . 'images/breakdance-icon.png';

        $icon_path  = trailingslashit( WP_PLUGIN_DIR ) . $bd_builder_icon;
        $icon_url   = file_exists( $icon_path ) ? plugins_url( $bd_builder_icon, dirname( __FILE__ ) ) : $bd_packaged_icon;
        $icon_url   = ( defined( 'BDQN_ICON' ) && 'yellow' === BDQN_ICON ) ? $bd_packaged_icon : $icon_url;
        $title_html = '<img src="' . esc_url( $icon_url ) . '" style="display:inline-block;padding-right:6px;vertical-align:middle;width:16px;height:16px;" alt="" />' . $bdqn_name;
        $title_html = wp_kses( $title_html, array(
            'img' => array(
                'src'   => array(),
                'style' => array(),
                'alt'   => array(),
            ),
        ) );

        /** Main menu item */
        $wp_admin_bar->add_node( array(
            'id'    => 'ddw-breakdance-quicknav',
            'title' => $title_html,
            'href'  => '#',
        ) );

        /** Add submenus (all group nodes!) */
        $this->add_templates_group( $wp_admin_bar );
        $this->add_settings_group( $wp_admin_bar );
        $this->add_plugin_support_group( $wp_admin_bar );
        $this->add_footer_group( $wp_admin_bar );
    }

    /**
     * Add group node for BD-edited Pages and all BD Template types.
     */
    private function add_templates_group( $wp_admin_bar ) {
        $wp_admin_bar->add_group( array(
            'id'     => 'bdqn-group-templates',
            'parent' => 'ddw-breakdance-quicknav',
        ) );
        
        $this->add_pages_submenu( $wp_admin_bar );
        $this->add_templates_submenu( $wp_admin_bar );
        $this->add_headers_submenu( $wp_admin_bar );
        $this->add_footers_submenu( $wp_admin_bar );
        $this->add_global_blocks_submenu( $wp_admin_bar );
        $this->add_popups_submenu( $wp_admin_bar );
    }
    
    /**
     * Add Breakdance-edited Pages submenu (just regular WordPress Pages).
     */
    private function add_pages_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-pages',
            'title'  => esc_html__( 'Pages (BD)', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'edit.php?post_type=page' ) ),
            'parent' => 'bdqn-group-templates',
        ) );

        $bd_pages = $this->get_breakdance_template_type( 'page' );
        
        if ( $bd_pages ) {
            foreach ( $bd_pages as $bd_page ) {
                $edit_link = site_url( '/?breakdance=builder&id=' . intval( $bd_page->ID ) );
        
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdqn-page-' . intval( $bd_page->ID ),
                    'title'  => esc_html( $bd_page->post_title ),
                    'href'   => esc_url( $edit_link ),
                    'parent' => 'bdqn-pages',
                ) );
            }  // end foreach
        }  // end if
    }
    
    /**
     * Add Breakdance Templates submenu.
     */
    private function add_templates_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-templates',
            'title'  => esc_html__( 'Templates', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_template' ) ),
            'parent' => 'bdqn-group-templates',
        ) );

        $templates = $this->get_breakdance_template_type( 'breakdance_template' );
        
        if ( $templates ) {
            foreach ( $templates as $template ) {
                /** Skip the internal BD Fallback templates */
                if ( strpos( $template->post_title, 'Fallback: ' ) === 0 ) {
                    continue;
                }
        
                $edit_link = site_url( '/?breakdance=builder&id=' . intval( $template->ID ) );
        
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdqn-template-' . intval( $template->ID ),
                    'title'  => esc_html( $template->post_title ),
                    'href'   => esc_url( $edit_link ),
                    'parent' => 'bdqn-templates',
                ) );
            }  // end foreach
        }  // end if
    }

    /**
     * Add Breakdance Headers submenu.
     */
    private function add_headers_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-headers',
            'title'  => esc_html__( 'Headers', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_header' ) ),
            'parent' => 'bdqn-group-templates',
        ) );

        $headers = $this->get_breakdance_template_type( 'breakdance_header' );
        
        if ( $headers ) {
            foreach ( $headers as $header ) {
                $edit_link = site_url( '/?breakdance=builder&id=' . intval( $header->ID ) );
        
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdqn-header-' . intval( $header->ID ),
                    'title'  => esc_html( $header->post_title ),
                    'href'   => esc_url( $edit_link ),
                    'parent' => 'bdqn-headers',
                ) );
            }  // end foreach
        }  // end if
    }

    /**
     * Add Breakdance Footers submenu.
     */
    private function add_footers_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-footers',
            'title'  => esc_html__( 'Footers', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_footer' ) ),
            'parent' => 'bdqn-group-templates',
        ) );

        $footers = $this->get_breakdance_template_type( 'breakdance_footer' );
        
        if ( $footers ) {
            foreach ( $footers as $footer ) {
                $edit_link = site_url( '/?breakdance=builder&id=' . intval( $footer->ID ) );
        
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdn-footer-' . intval( $footer->ID ),
                    'title'  => esc_html( $footer->post_title ),
                    'href'   => esc_url( $edit_link ),
                    'parent' => 'bdqn-footers',
                ) );
            }  // end foreach
        }  // end if
    }

    /**
     * Add Breakdance Global Blocks submenu.
     */
    private function add_global_blocks_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-global-blocks',
            'title'  => esc_html__( 'Global Blocks', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_block' ) ),
            'parent' => 'bdqn-group-templates',
        ) );

        $blocks = $this->get_breakdance_template_type( 'breakdance_block' );
        
        if ( $blocks ) {
            foreach ( $blocks as $block ) {
                $edit_link = site_url( '/?breakdance=builder&id=' . intval( $block->ID ) );
        
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdqn-block-' . intval( $block->ID ),
                    'title'  => esc_html( $block->post_title ),
                    'href'   => esc_url( $edit_link ),
                    'parent' => 'bdqn-global-blocks',
                ) );
            }  // end foreach
        }  // end if
    }

    /**
     * Add Breakdance Popups submenu.
     */
    private function add_popups_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-popups',
            'title'  => esc_html__( 'Popups', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_popup' ) ),
            'parent' => 'bdqn-group-templates',
        ) );

        $popups = $this->get_breakdance_template_type( 'breakdance_popup' );
        
        if ( $popups ) {
            foreach ( $popups as $popup ) {
                $edit_link = site_url( '/?breakdance=builder&id=' . intval( $popup->ID ) );
        
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdqn-popup-' . intval( $popup->ID ),
                    'title'  => esc_html( $popup->post_title ),
                    'href'   => esc_url( $edit_link ),
                    'parent' => 'bdqn-popups',
                ) );
            }  // end foreach
        }  // end if
    }

    /**
     * Add group node for actions & settings.
     */
    private function add_settings_group( $wp_admin_bar ) {
        $wp_admin_bar->add_group( array(
            'id'     => 'bdqn-group-settings',
            'parent' => 'ddw-breakdance-quicknav',
        ) );
        
        $this->add_actions_submenu( $wp_admin_bar );
        $this->add_settings_submenu( $wp_admin_bar );
    }
    
    /**
     * Add actions submenu.
     */
    private function add_actions_submenu( $wp_admin_bar ) {
        
        $icon_styles = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C17.5222 2 22 5.97778 22 10.8889C22 13.9556 19.5111 16.4444 16.4444 16.4444H14.4778C13.5556 16.4444 12.8111 17.1889 12.8111 18.1111C12.8111 18.5333 12.9778 18.9222 13.2333 19.2111C13.5 19.5111 13.6667 19.9 13.6667 20.3333C13.6667 21.2556 12.9 22 12 22C6.47778 22 2 17.5222 2 12C2 6.47778 6.47778 2 12 2ZM10.8111 18.1111C10.8111 16.0843 12.451 14.4444 14.4778 14.4444H16.4444C18.4065 14.4444 20 12.851 20 10.8889C20 7.1392 16.4677 4 12 4C7.58235 4 4 7.58235 4 12C4 16.19 7.2226 19.6285 11.324 19.9718C10.9948 19.4168 10.8111 18.7761 10.8111 18.1111ZM7.5 12C6.67157 12 6 11.3284 6 10.5C6 9.67157 6.67157 9 7.5 9C8.32843 9 9 9.67157 9 10.5C9 11.3284 8.32843 12 7.5 12ZM16.5 12C15.6716 12 15 11.3284 15 10.5C15 9.67157 15.6716 9 16.5 9C17.3284 9 18 9.67157 18 10.5C18 11.3284 17.3284 12 16.5 12ZM12 9C11.1716 9 10.5 8.32843 10.5 7.5C10.5 6.67157 11.1716 6 12 6C12.8284 6 13.5 6.67157 13.5 7.5C13.5 8.32843 12.8284 9 12 9Z"></path></svg></span> ';
        
        $edit_styles_link = site_url( '/?breakdance=builder&&mode=browse&returnUrl=' . admin_url( 'admin.php?page=breakdance_settings&tab=global_styles' ) );
        
        /** Edit Global Styles */
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-edit-global-styles',
            'title'  => $icon_styles . esc_html__( 'Edit Global Styles', 'breakdance-quicknav' ),
            'href'   => esc_url( $edit_styles_link ),
            'parent' => 'bdqn-group-settings',
            'meta'   => array( 'class' => 'has-icon', 'target' => '_blank' ),
        ) );
        
        $icon_forms = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 2V4H20.0066C20.5552 4 21 4.44495 21 4.9934V21.0066C21 21.5552 20.5551 22 20.0066 22H3.9934C3.44476 22 3 21.5551 3 21.0066V4.9934C3 4.44476 3.44495 4 3.9934 4H7V2H17ZM7 6H5V20H19V6H17V8H7V6ZM9 16V18H7V16H9ZM9 13V15H7V13H9ZM9 10V12H7V10H9ZM15 4H9V6H15V4Z"></path></svg></span> ';
        
        /** Form Submissions (for Breakdance Forms) */
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-form-submissions',
            'title'  => $icon_forms . esc_html__( 'Form Submissions', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'edit.php?post_type=breakdance_form_res' ) ),
            'parent' => 'bdqn-group-settings',
            'meta'   => array( 'class' => 'has-icon' ),
        ) );
        
        $icon_design = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21 20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3H20C20.5523 3 21 3.44772 21 4V20ZM11 5H5V19H11V5ZM19 13H13V19H19V13ZM19 5H13V11H19V5Z"></path></svg></span> ';
        
        /** Design Library */
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-design-library',
            'title'  => $icon_design . esc_html__( 'Design Library', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_design_library' ) ),
            'parent' => 'bdqn-group-settings',
            'meta'   => array( 'class' => 'has-icon' ),
        ) );
    }

    /**
     * Add Breakdance Settings submenu (parent node)
     */
    private function add_settings_submenu( $wp_admin_bar ) {
        
        $icon_settings = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5.32943 3.27158C6.56252 2.8332 7.9923 3.10749 8.97927 4.09446C10.1002 5.21537 10.3019 6.90741 9.5843 8.23385L20.293 18.9437L18.8788 20.3579L8.16982 9.64875C6.84325 10.3669 5.15069 10.1654 4.02952 9.04421C3.04227 8.05696 2.7681 6.62665 3.20701 5.39332L5.44373 7.63C6.02952 8.21578 6.97927 8.21578 7.56505 7.63C8.15084 7.04421 8.15084 6.09446 7.56505 5.50868L5.32943 3.27158ZM15.6968 5.15512L18.8788 3.38736L20.293 4.80157L18.5252 7.98355L16.7574 8.3371L14.6361 10.4584L13.2219 9.04421L15.3432 6.92289L15.6968 5.15512ZM8.97927 13.2868L10.3935 14.7011L5.09018 20.0044C4.69966 20.3949 4.06649 20.3949 3.67597 20.0044C3.31334 19.6417 3.28744 19.0699 3.59826 18.6774L3.67597 18.5902L8.97927 13.2868Z"></path></svg></span> ';
        
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-settings',
            'title'  => $icon_settings . esc_html__( 'Settings', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_settings' ) ),
            'parent' => 'bdqn-group-settings',
            'meta'   => array( 'class' => 'has-icon bdn-settings-separator' ),
        ) );

        $settings_submenus = array(
            'global_styles'    => __( 'Global Styles', 'breakdance-quicknav' ),
            'theme_disabler'   => __( 'Theme', 'breakdance-quicknav' ),
            'permissions'      => __( 'User Access', 'breakdance-quicknav' ),
            'maintenance-mode' => __( 'Maintenance', 'breakdance-quicknav' ),
            'bloat_eliminator' => __( 'Performance', 'breakdance-quicknav' ),
            'api_keys'         => __( 'API Keys', 'breakdance-quicknav' ),
            'post_types'       => __( 'Post Types', 'breakdance-quicknav' ),
            'advanced'         => __( 'Advanced', 'breakdance-quicknav' ),
            'privacy'          => __( 'Privacy', 'breakdance-quicknav' ),
            'design_library'   => __( 'Design Library', 'breakdance-quicknav' ),
            'header_footer'    => __( 'Custom Code', 'breakdance-quicknav' ),
            'tools'            => __( 'Tools', 'breakdance-quicknav' ),
        );
        
        /** Official extension */
        if ( defined( 'BREAKDANCE_AI_VERSION' ) ) {
            $settings_submenus[ 'ai' ] = __( 'AI Assistant', 'breakdance-quicknav' );
        }
        
        /** Official extension */
        if ( function_exists( 'Breakdance\MigrationMode\saveActivatingUserIp' ) ) {
            $settings_submenus[ 'migration-mode' ] = __( 'Migration Mode', 'breakdance-quicknav' );
        }
        
        /** Only if WooCommerce plugin is active */
        if ( class_exists( 'WooCommerce' ) ) {
            $settinngs_submenus[ 'woocommerce' ] = __( 'WooCommerce', 'breakdance-quicknav' );
        }

        /** License always at the bottom, before filter */
        $settings_submenus[ 'license' ] = __( 'License', 'breakdance-quicknav' );
        
        /** Make settings array filterable */
        apply_filters( 'ddw/quicknav/bd_settings', $settings_submenus );
        
        foreach ( $settings_submenus as $tab => $title ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-settings-' . sanitize_key( $tab ),
                'title'  => esc_html( $title ),
                'href'   => esc_url( admin_url( 'admin.php?page=breakdance_settings&tab=' . urlencode( $tab ) ) ),
                'parent' => 'bdqn-settings',
            ) );
        }  // end foreach
    }

    /**
     * Add group node for plugin support
     */
    private function add_plugin_support_group( $wp_admin_bar ) {
        $wp_admin_bar->add_group( array(
            'id'     => 'bdqn-group-plugins',
            'parent' => 'ddw-breakdance-quicknav',
        ) );
        
        $this->maybe_add_plugin_submenus( $wp_admin_bar );
    }
    
    /**
     * Add submenus for supported plugins - if they are active.
     */
    private function maybe_add_plugin_submenus( $wp_admin_bar ) {
        
        /**  Plugin: Headspin Copilot (free & Pro) */
        if ( defined( 'HSF_VERSION' ) ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-headspin',
                'title'  => esc_html__( 'Headspin Copilot', 'breakdance-quicknav' ),
                'href'   => esc_url( admin_url( 'admin.php?page=headspin' ) ),
                'parent' => 'bdqn-group-plugins',
            ) );
        }
        
        /** Plugin: Add Yabe Webfont (free & Pro) */
        if ( class_exists( '\Yabe\Webfont\Plugin' ) ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-yabe-webfont',
                'title'  => esc_html__( 'Yabe Webfont', 'breakdance-quicknav' ),
                'href'   => esc_url( admin_url( 'themes.php?page=yabe_webfont' ) ),
                'parent' => 'bdqn-group-plugins',
            ) );
        }
        
        /** Plugin: WPSix Exporter (premium) */
        if ( defined( 'WPSIX_EXPORTER_URL' ) ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-wpsix-exporter',
                'title'  => esc_html__( 'WPSix Exporter', 'breakdance-quicknav' ),
                'href'   => esc_url( admin_url( 'admin.php?page=wpsix_exporter' ) ),
                'parent' => 'bdqn-group-plugins',
            ) );
        }
        
        /** Plugin: Breakdance Reading Time Calculator (free) */
        if ( function_exists( 'bd_reading_time_menu' ) ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-bdrtc',
                'title'  => esc_html__( 'Reading Time Calculator', 'breakdance-quicknav' ),
                'href'   => esc_url( admin_url( 'admin.php?page=bd-reading-time' ) ),
                'parent' => 'bdqn-group-plugins',
            ) );
        }
    }
    
    /**
     * Add group node for footer items (Links & About)
     */
    private function add_footer_group( $wp_admin_bar ) {
        
        if ( defined( 'BDQN_DISABLE_FOOTER' ) && 'yes' === BDQN_DISABLE_FOOTER ) {
            return $wp_admin_bar;
        }
        
        $wp_admin_bar->add_group( array(
            'id'     => 'bdqn-group-footer',
            'parent' => 'ddw-breakdance-quicknav',
            'meta'   => array( 'class' => 'ab-sub-secondary' ),
        ) );
        
        $this->add_links_submenu( $wp_admin_bar );
        $this->add_about_submenu( $wp_admin_bar );
    }
    
    /**
     * Add Links submenu
     */
    private function add_links_submenu( $wp_admin_bar ) {
        
        $icon = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6V8H5V19H16V14H18V20C18 20.5523 17.5523 21 17 21H4C3.44772 21 3 20.5523 3 20V7C3 6.44772 3.44772 6 4 6H10ZM21 3V11H19L18.9999 6.413L11.2071 14.2071L9.79289 12.7929L17.5849 5H13V3H21Z"></path></svg></span> ';
        
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-links',
            'title'  => $icon . esc_html__( 'Links', 'breakdance-quicknav' ),
            'href'   => '#',
            'parent' => 'bdqn-group-footer',
            'meta'   => array( 'class' => 'has-icon' ),
        ) );

        $links = array(
            'breakdance' => array(
                'title' => __( 'Breakdance HQ', 'breakdance-quicknav' ),
                'url'   => 'https://breakdance.com/',
            ),
            'breakdance-learn' => array(
                'title' => __( 'Learn Breakdance (Tutorials)', 'breakdance-quicknav' ),
                'url'   => 'https://breakdance.com/learn/',
            ),
            'breakdance-docs' => array(
                'title' => __( 'Breakdance Documentation', 'breakdance-quicknav' ),
                'url'   => 'https://breakdance.com/documentation/',
            ),
            'breakdance-youtube' => array(
                'title' => __( 'Breakdance YouTube Channel', 'breakdance-quicknav' ),
                'url'   => 'https://www.youtube.com/@OfficialBreakdance/featured',
            ),
            'breakdance-fb-group' => array(
                'title' => __( 'Breakdance FB Group', 'breakdance-quicknav' ),
                'url'   => 'https://www.facebook.com/groups/breakdanceofficial',
            ),
            'breakdance4fun' => array(
                'title' => __( 'breakdance4fun (Tutorials, Tips, Resources)', 'breakdance-quicknav' ),
                'url'   => 'https://breakdance4fun.supadezign.com/',
            ),
            'bd-discord-unofficial' => array(
                'title' => __( 'Breakdance Unofficial Discord', 'breakdance-quicknav' ),
                'url'   => 'https://discord.com/channels/523286444283002890/530617461775532042',
            ),
            'headspin'  => array(
                'title' => __( 'Headspin', 'breakdance-quicknav' ),
                'url'   => 'https://headspinui.com/',
            ),
            'moreblocks' => array(
                'title' => __( 'Moreblocks', 'breakdance-quicknav' ),
                'url'   => 'https://moreblocks.com/',
            ),
            'breakerblocks' => array(
                'title' => __( 'Breakerblocks', 'breakdance-quicknav' ),
                'url'   => 'https://breakerblocks.com/',
            ),
            'bdlibraryawesome' => array(
                'title' => __( 'BD Library Awesome', 'breakdance-quicknav' ),
                'url'   => 'https://bdlibraryawesome.com/',
            ),
            'bdblox' => array(
                'title' => __( 'Bdblox', 'breakdance-quicknav' ),
                'url'   => 'https://bdblox.com/',
            ),
        );

        foreach ( $links as $id => $info ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-link-' . sanitize_key( $id ),
                'title'  => esc_html( $info[ 'title' ] ),
                'href'   => esc_url( $info[ 'url' ] ),
                'parent' => 'bdqn-links',
                'meta'   => array( 'target' => '_blank', 'rel' => 'nofollow noopener noreferrer' ),
            ) );
        }  // end foreach
    }

    /**
     * Add About submenu
     */
    private function add_about_submenu( $wp_admin_bar ) {
        
        $icon = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.841 15.659L18.017 15.836L18.1945 15.659C19.0732 14.7803 20.4978 14.7803 21.3765 15.659C22.2552 16.5377 22.2552 17.9623 21.3765 18.841L18.0178 22.1997L14.659 18.841C13.7803 17.9623 13.7803 16.5377 14.659 15.659C15.5377 14.7803 16.9623 14.7803 17.841 15.659ZM12 14V16C8.68629 16 6 18.6863 6 22H4C4 17.6651 7.44784 14.1355 11.7508 14.0038L12 14ZM12 1C15.315 1 18 3.685 18 7C18 10.2397 15.4357 12.8776 12.225 12.9959L12 13C8.685 13 6 10.315 6 7C6 3.76034 8.56434 1.12237 11.775 1.00414L12 1ZM12 3C9.78957 3 8 4.78957 8 7C8 9.21043 9.78957 11 12 11C14.2104 11 16 9.21043 16 7C16 4.78957 14.2104 3 12 3Z"></path></svg></span> ';
        
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-about',
            'title'  => $icon . esc_html__( 'About', 'breakdance-quicknav' ),
            'href'   => '#',
            'parent' => 'bdqn-group-footer',
            'meta'   => array( 'class' => 'has-icon' ),
        ) );

        $about_links = array(
            'author' => array(
                'title' => __( 'Author: David Decker', 'breakdance-quicknav' ),
                'url'   => 'https://deckerweb.de/',
            ),
            'github' => array(
                'title' => __( 'Plugin on GitHub', 'breakdance-quicknav' ),
                'url'   => 'https://github.com/deckerweb/breakdance-quicknav',
            ),
            'kofi' => array(
                'title' => __( 'Buy Me a Coffee', 'breakdance-quicknav' ),
                'url'   => 'https://ko-fi.com/deckerweb',
            ),
        );

        foreach ( $about_links as $id => $info ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-about-' . sanitize_key( $id ),
                'title'  => esc_html( $info[ 'title' ] ),
                'href'   => esc_url( $info[ 'url' ] ),
                'parent' => 'bdqn-about',
                'meta'   => array( 'target' => '_blank', 'rel' => 'nofollow noopener noreferrer' ),
            ) );
        }  // end foreach
    }
    
    /**
     * Show the Admin Bar also in Block Editor full screen mode.
     */
    public function adminbar_block_editor_fullscreen() {
        
        if ( ! is_admin_bar_showing() ) return;
        
        /**
         * Depending on user color scheme get proper bg color value for admin bar.
         */
        $user_color_scheme = get_user_option( 'admin_color' );
        $admin_scheme      = $this->get_scheme_colors();
        
        $bg_color = $admin_scheme[ $user_color_scheme ][ 'bg' ];
        
        $inline_css_block_editor = sprintf(
            '
                @media (min-width: 600px) {
                    body.is-fullscreen-mode .block-editor__container {
                        top: var(--wp-admin--admin-bar--height);
                    }
                }
                
                @media (min-width: 782px) {
                    body.js.is-fullscreen-mode #wpadminbar {
                        display: block;
                    }
                
                    body.is-fullscreen-mode .block-editor__container {
                        min-height: calc(100vh - var(--wp-admin--admin-bar--height));
                    }
                
                    body.is-fullscreen-mode .edit-post-layout .editor-post-publish-panel {
                        top: var(--wp-admin--admin-bar--height);
                    }
                    
                    .edit-post-fullscreen-mode-close.components-button {
                        background: %s;
                    }
                    
                    .edit-post-fullscreen-mode-close.components-button::before {
                        box-shadow: none;
                    }
                }
                
                @media (min-width: 783px) {
                    .is-fullscreen-mode .interface-interface-skeleton {
                        top: var(--wp-admin--admin-bar--height);
                    }
                }
            ',
            sanitize_hex_color( $bg_color )
        );
        
        wp_add_inline_style( 'wp-block-editor', $inline_css_block_editor );
        
        $inline_css_edit_site = sprintf(
            '
                body.is-fullscreen-mode .edit-site {
                    top: var(--wp-admin--admin-bar--height);
                }
                
                body.is-fullscreen-mode .edit-site-layout__canvas-container {
                    top: calc( var(--wp-admin--admin-bar--height) * -1 );
                }
                
                .edit-site-editor__view-mode-toggle .edit-site-editor__view-mode-toggle-icon img,
                .edit-site-editor__view-mode-toggle .edit-site-editor__view-mode-toggle-icon svg {
                        background: %s;
                }
            ',
            sanitize_hex_color( $bg_color )
        );
        
        wp_add_inline_style( 'wp-edit-site', $inline_css_edit_site );
        
        add_action( 'admin_bar_menu', array( $this, 'remove_adminbar_nodes' ), 999 );
    }
    
    /**
     * Remove Admin Bar nodes.
     */
    public function remove_adminbar_nodes( $wp_admin_bar ) {
        $wp_admin_bar->remove_node( 'wp-logo' );  
    }
    
    /**
     * Add additional plugin related info to the Site Health Debug Info section.
     *
     * @link https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/
     *
     * @param array $debug_info Array holding all Debug Info items.
     * @return array Modified array of Debug Info.
     */
    public function site_health_debug_info( $debug_info ) {
    
        $string_undefined = esc_html_x( 'Undefined', 'Site Health Debug info', 'breakdance-quicknav' );
        $string_enabled   = esc_html_x( 'Enabled', 'Site Health Debug info', 'breakdance-quicknav' );
        $string_disabled  = esc_html_x( 'Disabled', 'Site Health Debug info', 'breakdance-quicknav' );
        $string_value     = ' – ' . esc_html_x( 'value', 'Site Health Debug info', 'breakdance-quicknav' ) . ': ';
        $string_version   = defined( '__BREAKDANCE_VERSION' ) ? __BREAKDANCE_VERSION : '';
    
        /** Add our Debug info */
        $debug_info[ 'breakdance-quicknav' ] = array(
            'label'  => esc_html__( 'Breakdance QuickNav', 'breakdance-quicknav' ) . ' (' . esc_html__( 'Plugin', 'breakdance-quicknav' ) . ')',
            'fields' => array(
    
                /** Various values */
                'bdqn_plugin_version' => array(
                    'label' => esc_html__( 'Plugin version', 'breakdance-quicknav' ),
                    'value' => self::VERSION,
                ),
                'bdqn_install_type' => array(
                    'label' => esc_html__( 'WordPress Install Type', 'breakdance-quicknav' ),
                    'value' => ( is_multisite() ? esc_html__( 'Multisite install', 'breakdance-quicknav' ) : esc_html__( 'Single Site install', 'breakdance-quicknav' ) ),
                ),
    
                /** Breakdance QuickNav constants */
                'BDQN_VIEW_CAPABILITY' => array(
                    'label' => 'BDQN_VIEW_CAPABILITY',
                    'value' => ( ! defined( 'BDQN_VIEW_CAPABILITY' ) ? $string_undefined : ( BDQN_VIEW_CAPABILITY ? $string_enabled : $string_disabled ) ),
                ),
                'BDQN_ENABLED_USERS' => array(
                    'label' => 'BDQN_ENABLED_USERS',
                    'value' => ( ! defined( 'BDQN_ENABLED_USERS' ) ? $string_undefined : ( BDQN_ENABLED_USERS ? $string_enabled . $string_value . implode( ', ', array_map( 'absint', BDQN_ENABLED_USERS ) ) : $string_disabled ) ),
                ),
                'BDQN_NAME_IN_ADMINBAR' => array(
                    'label' => 'BDQN_NAME_IN_ADMINBAR',
                    'value' => ( ! defined( 'BDQN_NAME_IN_ADMINBAR' ) ? $string_undefined : ( BDQN_NAME_IN_ADMINBAR ? $string_enabled . $string_value . esc_html( BDQN_NAME_IN_ADMINBAR )  : $string_disabled ) ),
                ),
                'BDQN_ICON' => array(
                    'label' => 'BDQN_ICON',
                    'value' => ( ! defined( 'BDQN_ICON' ) ? $string_undefined : ( BDQN_ICON ? $string_enabled . $string_value . sanitize_key( BDQN_ICON ) : $string_disabled ) ),
                ),
                'BDQN_NUMBER_TEMPLATES' => array(
                    'label' => 'BDQN_NUMBER_TEMPLATES',
                    'value' => ( ! defined( 'BDQN_NUMBER_TEMPLATES' ) ? $string_undefined : ( BDQN_NUMBER_TEMPLATES ? $string_enabled . $string_value . absint( BDQN_NUMBER_TEMPLATES ) : $string_disabled ) ),
                ),
                'BDQN_DISABLE_FOOTER' => array(
                    'label' => 'BDQN_DISABLE_FOOTER',
                    'value' => ( ! defined( 'BDQN_DISABLE_FOOTER' ) ? $string_undefined : ( BDQN_DISABLE_FOOTER ? $string_enabled : $string_disabled ) ),
                ),
                'bdqn_bd_version' => array(
                    'label' => esc_html( 'Breakdance Pro Version', 'breakdance-quicknav' ),
                    'value' => ( ! defined( '__BREAKDANCE_VERSION' ) ? esc_html__( 'Plugin not installed', 'breakdance-quicknav' ) : $string_version ),
                ),
            ),  // end array
        );
    
        /** Return modified Debug Info array */
        return $debug_info;
    }
    
}  // end of class

/** Don't do anything if Breakdance Navigator plugin is already active */
if ( ! class_exists( 'Breakdance_Navigator' ) ) {
    new DDW_Breakdance_QuickNav();
}
    
endif;


if ( ! function_exists( 'ddw_bdqn_pluginrow_meta' ) ) :
    
add_filter( 'plugin_row_meta', 'ddw_bdqn_pluginrow_meta', 10, 2 );
/**
 * Add plugin related links to plugin page.
 *
 * @param array  $ddwp_meta (Default) Array of plugin meta links.
 * @param string $ddwp_file File location of plugin.
 * @return array $ddwp_meta (Modified) Array of plugin links/ meta.
 */
function ddw_bdqn_pluginrow_meta( $ddwp_meta, $ddwp_file ) {
 
     if ( ! current_user_can( 'install_plugins' ) ) return $ddwp_meta;
 
     /** Get current user */
     $user = wp_get_current_user();
     
     /** Build Newsletter URL */
     $url_nl = sprintf(
         'https://deckerweb.us2.list-manage.com/subscribe?u=e09bef034abf80704e5ff9809&amp;id=380976af88&amp;MERGE0=%1$s&amp;MERGE1=%2$s',
         esc_attr( $user->user_email ),
         esc_attr( $user->user_firstname )
     );
     
     /** List additional links only for this plugin */
     if ( $ddwp_file === trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . basename( __FILE__ ) ) {
         $ddwp_meta[] = sprintf(
             '<a class="button button-inline" href="https://ko-fi.com/deckerweb" target="_blank" rel="nofollow noopener noreferrer" title="%1$s">❤ <b>%1$s</b></a>',
             esc_html_x( 'Donate', 'Plugins page listing', 'breakdance-quicknav' )
         );
 
         $ddwp_meta[] = sprintf(
             '<a class="button-primary" href="%1$s" target="_blank" rel="nofollow noopener noreferrer" title="%2$s">⚡ <b>%2$s</b></a>',
             $url_nl,
             esc_html_x( 'Join our Newsletter', 'Plugins page listing', 'breakdance-quicknav' )
         );
     }  // end if
 
     return apply_filters( 'ddw/admin_extras/pluginrow_meta', $ddwp_meta );
 
 }  // end function
 
 endif;