<?php
/**
 * Forked from "Breakdance Navigator" by Peter Kulcsár
 * License: GPL v2 or later
 * GitHub Repository: https://github.com/beamkiller/breakdance-navigator
 */
 
/*
Plugin Name: Breakdance QuickNav
Plugin URI: https://github.com/deckerweb/breakdance-quicknav
Description: Adds a quick-access navigator to the WordPress Admin Bar (Toolbar). It allows easy access to Breakdance Templates, Headers, Footers, Global Blocks, Popups, and Pages edited with Breakdance, along with some other essential settings.
Version: 1.0.0
Author: David Decker
Author URI: https://deckerweb.de/
Text Domain: breakdance-quicknav
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/** Prevent direct access */
if ( ! defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly.
}


if ( ! class_exists( 'DDW_Breakdance_QuickNav' ) ) {

    class DDW_Breakdance_QuickNav {

        public function __construct() {           
            add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_bar_styles' ) );  // for Admin
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_bar_styles' ) );     // for front-end
        }

        /**
         * Enqueue custom styles for the admin bar.
         */
        public function enqueue_admin_bar_styles() {      
            $inline_css = sprintf(
                '
                /* Style for the separator */
                #wp-admin-bar-ddw-breakdance-quicknav > .ab-sub-wrapper #wp-admin-bar-bdqn-settings {
                    border-bottom: 1px dashed rgba(255, 255, 255, 0.33);
                    /* margin: 0 0 5px 0; */
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
            if ( ! defined( '__BREAKDANCE_VERSION' ) ) {
                return;
            }
            
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
            $title_html = '<img src="' . esc_url( $icon_url ) . '" style="width:16px;height:16px;padding-right:6px;vertical-align:middle;" alt="">' . $bdqn_name;
            $title_html = wp_kses( $title_html, array(
                'img' => array(
                    'src'   => array(),
                    'style' => array(),
                    'alt'   => array(),
                ),
            ) );

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
         * Add all Breakdance-edited Pages
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
         * Get all Breakdance-edited Pages. Helper function.
         */
        private function get_breakdance_pages() {
            $args = array(
                'post_type'      => 'page',
                'posts_per_page' => 10,
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
         * Add up to 10 Breakdance Templates (child nodes)
         */
        private function add_templates_to_admin_bar( $wp_admin_bar ) {
            $templates = $this->get_breakdance_templates();

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
         * Get Breakdance Templates. Helper function.
         */
        private function get_breakdance_templates() {
            $args = array(
                'post_type'      => 'breakdance_template',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
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
         * Add up to 10 Breakdance Header templates (child nodes)
         */
        private function add_headers_to_admin_bar( $wp_admin_bar ) {
            $headers = $this->get_breakdance_headers();

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
         * Get Breakdance Header templates. Helper function.
         */
        private function get_breakdance_headers() {
            $args = array(
                'post_type'      => 'breakdance_header',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
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
         * Add up to 10 Breakdance Footer templates (child nodes)
         */
        private function add_footers_to_admin_bar( $wp_admin_bar ) {
            $footers = $this->get_breakdance_footers();

            if ( $footers ) {
                foreach ( $footers as $footer ) {
                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $footer->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdn-footer-' . intval( $footer->ID ),
                        'title'  => esc_html( $footer->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdqn-footers',
                    ) );
                }
            }
        }

        /**
         * Get Breakdance Footer templates. Helper function.
         */
        private function get_breakdance_footers() {
            $args = array(
                'post_type'      => 'breakdance_footer',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
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
         * Add up to 10 Breakdance Global Block templates (child nodes)
         */
        private function add_global_blocks_to_admin_bar( $wp_admin_bar ) {
            $blocks = $this->get_breakdance_global_blocks();

            if ( $blocks ) {
                foreach ( $blocks as $block ) {
                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $block->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdqn-block-' . intval( $block->ID ),
                        'title'  => esc_html( $block->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdqn-global-blocks',
                    ) );
                }
            }
        }

        /**
         * Get Breakdance Global Blocks. Helper function.
         */
        private function get_breakdance_global_blocks() {
            $args = array(
                'post_type'      => 'breakdance_block',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
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
         * Add up to 10 Breakdance Popup templates (child nodes)
         */
        private function add_popups_to_admin_bar( $wp_admin_bar ) {
            $popups = $this->get_breakdance_popups();

            if ( $popups ) {
                foreach ( $popups as $popup ) {
                    $edit_link = site_url( '/?breakdance=builder&id=' . intval( $popup->ID ) );

                    $wp_admin_bar->add_node( array(
                        'id'     => 'bdqn-popup-' . intval( $popup->ID ),
                        'title'  => esc_html( $popup->post_title ),
                        'href'   => esc_url( $edit_link ),
                        'parent' => 'bdqn-popups',
                    ) );
                }
            }
        }

        /**
         * Get Breakdance Popup templates. Helper function.
         */
        private function get_breakdance_popups() {
            $args = array(
                'post_type'      => 'breakdance_popup',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            return get_posts( $args );
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
                'woocommerce'      => __( 'WooCommerce', 'breakdance-quicknav' ),
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
                'license'          => __( 'License', 'breakdance-quicknav' ),
            );
            
            if ( defined( 'BREAKDANCE_AI_VERSION' ) ) {
                $settings_submenus[ 'ai' ] = __( 'AI Assistant', 'breakdance-quicknav' );
            }
            
            if ( function_exists( 'Breakdance\MigrationMode\saveActivatingUserIp' ) ) {
                $settings_submenus[ 'migration-mode' ] = __( 'Migration Mode', 'breakdance-quicknav' );
            }

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
         * Add Headspin submenu if the plugin is active (Headspin Copilot)
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
                return;
            }
            
            $wp_admin_bar->add_group( array(
                'id'     => 'bdqn-footer',
                'parent' => 'ddw-breakdance-quicknav',
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
                    'meta'   => array( 'target' => '_blank' ),
                ) );
            }
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
                'author'       => array(
                    'title' => __( 'Author: David Decker', 'breakdance-quicknav' ),
                    'url'   => 'https://deckerweb.de/',
                ),
                'github'       => array(
                    'title' => __( 'Plugin on GitHub', 'breakdance-quicknav' ),
                    'url'   => 'https://github.com/deckerweb/breakdance-quicknav',
                ),
                'buymeacoffee' => array(
                    'title' => __( 'Buy Me a Coffee', 'breakdance-quicknav' ),
                    'url'   => 'https://buymeacoffee.com/daveshine',
                ),
            );

            foreach ( $about_links as $id => $info ) {
                $wp_admin_bar->add_node( array(
                    'id'     => 'bdqn-about-' . sanitize_key( $id ),
                    'title'  => esc_html( $info[ 'title' ] ),
                    'href'   => esc_url( $info[ 'url' ] ),
                    'parent' => 'bdqn-about',
                    'meta'   => array( 'target' => '_blank' ),
                ) );
            }
        }
    }

    /** Don't do anything if Breakdance Navigator plugin is already active */
    if ( ! class_exists( 'Breakdance_Navigator' ) ) {
        new DDW_Breakdance_QuickNav();
    }
    
}  // end of class