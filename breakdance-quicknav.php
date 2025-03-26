<?php
/*
Forked from "Breakdance Navigator" by Peter Kulcsár
License: GPL v2 or later
GitHub Repository: https://github.com/beamkiller/breakdance-navigator
Original Copyright: © 2024, Peter Kulcsár
*/
 
/*
Plugin Name:  Breakdance QuickNav
Plugin URI:   https://github.com/deckerweb/breakdance-quicknav
Description:  Adds a quick-access navigator (aka QuickNav) to the WordPress Admin Bar (Toolbar). It allows easy access to Breakdance Templates, Headers, Footers, Global Blocks, Popups, and Pages edited with Breakdance, along with some other essential settings.
Project:      Code Snippet: DDW Breakdance QuickNav
Version:      1.1.0
Author:       David Decker – DECKERWEB
Author URI:   https://deckerweb.de/
Text Domain:  breakdance-quicknav
Domain Path:  /languages/
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Requires WP:  6.7
Requires PHP: 7.4

Original Copyright: © 2024 Peter Kulcsár
Copyright:    © 2025, David Decker – DECKERWEB

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
2025-03-??	1.1.0       New: Adjust the number of shown templates via constant (default: up to 20)
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
        $admin_scheme      = $this->get_scheme_colors();
        
        $base_color  = $admin_scheme[ $user_color_scheme ][ 'base' ];
        $hover_color = $admin_scheme[ $user_color_scheme ][ 'hover' ];
        
        $inline_css = sprintf(
            '
            /* Style for the separator */
            #wp-admin-bar-ddw-breakdance-quicknav > .ab-sub-wrapper #wp-admin-bar-bdqn-settings {
                border-bottom: 1px dashed rgba(255, 255, 255, 0.33);
                padding-bottom: 5px;
            }
            '
        );
        
        if ( is_admin_bar_showing() ) {
            wp_add_inline_style( 'admin-bar', $inline_css );
        }
    }

    /**
     * Adds the main Breakdance menu and its submenus to the Admin Bar.
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
     */
    public function add_admin_bar_menu( $wp_admin_bar ) {
        
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

        /** Add submenus */
        $this->add_pages_submenu( $wp_admin_bar );
        $this->add_templates_submenu( $wp_admin_bar );
        $this->add_headers_submenu( $wp_admin_bar );
        $this->add_footers_submenu( $wp_admin_bar );
        $this->add_global_blocks_submenu( $wp_admin_bar );
        $this->add_popups_submenu( $wp_admin_bar );
        $this->add_form_submissions_submenu( $wp_admin_bar );
        $this->add_design_library_submenu( $wp_admin_bar );
        $this->add_settings_submenu( $wp_admin_bar );
        $this->add_plugin_support_group( $wp_admin_bar );  // group node
        $this->add_headspin_submenu( $wp_admin_bar );
        $this->add_yabe_webfont_submenu( $wp_admin_bar );
        $this->add_wpsix_exporter_submenu( $wp_admin_bar );
        $this->add_footer_group( $wp_admin_bar );  // group node
        $this->add_links_submenu( $wp_admin_bar );
        $this->add_about_submenu( $wp_admin_bar );
    }

    /**
     * Add Pages submenu (just regular WordPress Pages)
     * NOTE: This sets the parent item; no Breakdance related stuff here, yet.
     */
    private function add_pages_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-pages',
            'title'  => esc_html__( 'Pages', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'edit.php?post_type=page' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );

        $this->add_breakdance_pages_to_admin_bar( $wp_admin_bar );
    }

    /**
     * Add up to 10 Breakdance-edited Pages
     */
    private function add_breakdance_pages_to_admin_bar( $wp_admin_bar ) {
        $bd_pages = $this->get_breakdance_pages();

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
     * Number of templates/pages to query for. Can be tweaked via constant.
     *   (Helper function)
     *
     * @return int Number of templates.
     */
    private function number_of_templates() {
            
        $number_of_templates = ( defined( 'BDQN_NUMBER_TEMPLATES' ) ) ? (int) BDQN_NUMBER_TEMPLATES : self::VERSION;
        
        return $number_of_templates;
    }
    
    /**
     * Get all Breakdance-edited Pages. Helper function.
     */
    private function get_breakdance_pages() {
        $args = array(
            'post_type'      => 'page',
            'posts_per_page' => absint( $this->number_of_templates() ),
            'post_status'    => 'publish',
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_breakdance_data',  // only BD-edited pages have that
                    'compare' => 'EXISTS',
                ),
            ),
        );
        return get_posts( $args );
    }

    /**
     * Get items of a Breakdance template type. Helper function.
     *
     * @uses get_posts()
     *
     * @param string $post_type Slug of post type to query for.
     */
    private function get_breakdance_template_type( $post_type ) {
        $args = array(
            'post_type'      => sanitize_key( $post_type ),
            'posts_per_page' => absint( $this->number_of_templates() ),
            'post_status'    => 'publish',
            'orderby'        => 'modified',
            'order'          => 'DESC',
        );
        
        apply_filters( 'ddw/quicknav/bd_get_template_type', $args, $post_type );
        
        return get_posts( $args );
    }
    
    /**
     * Add Breakdance Templates submenu (parent node)
     */
    private function add_templates_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-templates',
            'title'  => esc_html__( 'Templates', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_template' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );

        $this->add_templates_to_admin_bar( $wp_admin_bar );
    }

    /**
     * Add Breakdance Templates (child nodes)
     */
    private function add_templates_to_admin_bar( $wp_admin_bar ) {
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
     * Add Breakdance Headers submenu (parent node)
     */
    private function add_headers_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-headers',
            'title'  => esc_html__( 'Headers', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_header' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );

        $this->add_headers_to_admin_bar( $wp_admin_bar );
    }

    /**
     * Add Breakdance Header templates (child nodes)
     */
    private function add_headers_to_admin_bar( $wp_admin_bar ) {
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
     * Add Breakdance Footers submenu (parent node)
     */
    private function add_footers_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-footers',
            'title'  => esc_html__( 'Footers', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_footer' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );

        $this->add_footers_to_admin_bar( $wp_admin_bar );
    }

    /**
     * Add Breakdance Footer templates (child nodes)
     */
    private function add_footers_to_admin_bar( $wp_admin_bar ) {
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
     * Add Breakdance Global Blocks submenu (parent node)
     */
    private function add_global_blocks_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-global-blocks',
            'title'  => esc_html__( 'Global Blocks', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_block' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );

        $this->add_global_blocks_to_admin_bar( $wp_admin_bar );
    }

    /**
     * Add Breakdance Global Block templates (child nodes)
     */
    private function add_global_blocks_to_admin_bar( $wp_admin_bar ) {
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
     * Add Breakdance Popups submenu (parent node)
     */
    private function add_popups_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-popups',
            'title'  => esc_html__( 'Popups', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_popup' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );

        $this->add_popups_to_admin_bar( $wp_admin_bar );
    }

    /**
     * Add Breakdance Popup templates (child nodes)
     */
    private function add_popups_to_admin_bar( $wp_admin_bar ) {
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
     * Add Form Submissions submenu (for Breakdance Forms)
     */
    private function add_form_submissions_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-form-submissions',
            'title'  => esc_html__( 'Form Submissions', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'edit.php?post_type=breakdance_form_res' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );
    }

    /**
     * Add Breakdance Design Library submenu
     */
    private function add_design_library_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-design-library',
            'title'  => esc_html__( 'Design Library', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_design_library' ) ),
            'parent' => 'ddw-breakdance-quicknav',
        ) );
    }

    /**
     * Add Breakdance Settings submenu (parent node)
     */
    private function add_settings_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-settings',
            'title'  => esc_html__( 'Settings', 'breakdance-quicknav' ),
            'href'   => esc_url( admin_url( 'admin.php?page=breakdance_settings' ) ),
            'parent' => 'ddw-breakdance-quicknav',
            'meta'   => array( 'class' => 'bdn-settings-separator' ),
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
        }
    }

    /**
     * Add group node for plugin support
     */
    private function add_plugin_support_group( $wp_admin_bar ) {
        $wp_admin_bar->add_group( array(
            'id'     => 'bdqn-plugins',
            'parent' => 'ddw-breakdance-quicknav',
        ) );
    }
    
    /**
     * Add Headspin Copilot submenu if the plugin is active
     */
    private function add_headspin_submenu( $wp_admin_bar ) {

        if ( defined( 'HSF_VERSION' ) ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-headspin',
                'title'  => esc_html__( 'Headspin Copilot', 'breakdance-quicknav' ),
                'href'   => esc_url( admin_url( 'admin.php?page=headspin' ) ),
                'parent' => 'bdqn-plugins',
            ) );
        }
    }

    /**
     * Add Yabe Webfont (free & Pro) submenu if the plugin is active
     */
    private function add_yabe_webfont_submenu( $wp_admin_bar ) {
    
        if ( class_exists( '\Yabe\Webfont\Plugin' ) ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-yabe-webfont',
                'title'  => esc_html__( 'Yabe Webfont', 'breakdance-quicknav' ),
                'href'   => esc_url( admin_url( 'themes.php?page=yabe_webfont' ) ),
                'parent' => 'bdqn-plugins',
            ) );
        }
    }
    
    /**
     * Add WPSix Exporter submenu if the plugin is active
     */
    private function add_wpsix_exporter_submenu( $wp_admin_bar ) {
    
        if ( defined( 'WPSIX_EXPORTER_URL' ) ) {
            $wp_admin_bar->add_node( array(
                'id'     => 'bdqn-wpsix-exporter',
                'title'  => esc_html__( 'WPSix Exporter', 'breakdance-quicknav' ),
                'href'   => esc_url( admin_url( 'admin.php?page=wpsix_exporter' ) ),
                'parent' => 'bdqn-plugins',
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
            'id'     => 'bdqn-footer',
            'parent' => 'ddw-breakdance-quicknav',
            'meta'   => array( 'class' => 'ab-sub-secondary' ),
        ) );
    }
    
    /**
     * Add Links submenu
     */
    private function add_links_submenu( $wp_admin_bar ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-links',
            'title'  => esc_html__( 'Links', 'breakdance-quicknav' ),
            'href'   => '#',
            'parent' => 'bdqn-footer',
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
        $wp_admin_bar->add_node( array(
            'id'     => 'bdqn-about',
            'title'  => esc_html__( 'About', 'breakdance-quicknav' ),
            'href'   => '#',
            'parent' => 'bdqn-footer',
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
        
        $inline_css = sprintf(
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
        
        wp_add_inline_style( 'wp-block-editor', $inline_css );
        
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
                'BDQN_NAME_IN_ADMINBAR' => array(
                    'label' => 'BDQN_NAME_IN_ADMINBAR',
                    'value' => ( ! defined( 'BDQN_NAME_IN_ADMINBAR' ) ? $string_undefined : ( BDQN_NAME_IN_ADMINBAR ? $string_enabled . $string_value . esc_html( BDQN_NAME_IN_ADMINBAR )  : $string_disabled ) ),
                ),
                'BDQN_ICON' => array(
                    'label' => 'BDQN_ICON',
                    'value' => ( ! defined( 'BDQN_ICON' ) ? $string_undefined : ( BDQN_ICON ? $string_enabled . $string_value . sanitize_key( BDQN_ICON ) : $string_disabled ) ),
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