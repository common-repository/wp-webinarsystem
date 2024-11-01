<?php
/*
Plugin Name: System Snapshot Report
Plugin URI: http://reaktivstudios.com
Description: Admin related functions for doing a site audit
Version: 1.0.1
Author: Reaktiv Studios
Author URI: http://reaktivstudios.com

	Copyright 2013 Andrew Norcross

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

	The original code (and inspiration) was ported from Easy Digital Downloads
*/

// Plugin Folder Path
if ( ! defined( 'SSRP_DIR' ) ) {
    define( 'SSRP_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'SSRP_VER' ) ) {
    define( 'SSRP_VER', '1.0.1' );
}

/**
 * Start up the engine
 * Reaktiv_Audit_Report class.
 */
class WPWS_System_Snapshot_Report
{
    /**
     * Static property to hold our singleton instance
     *
     * (default value: false)
     *
     * @var bool
     * @access public
     * @static
     */
    static $instance = false;

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @access public
     * @static
     * @return
     */
    public static function getInstance() {
        if ( !self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * helper function for number conversions
     *
     * @access public
     * @param mixed $v
     * @return int
     */
    public function num_convt( $v ) {
        $l   = substr( $v, -1 );
        $ret = substr( $v, 0, -1 );

        switch ( strtoupper( $l ) ) {
            case 'P': // fall-through
            case 'T': // fall-through
            case 'G': // fall-through
            case 'M': // fall-through
            case 'K': // fall-through
                $ret *= 1024;
                break;
            default:
                break;
        }

        return $ret;
    }

    /**
     * generate data for report
     *
     * @return string
     */

    public function snapshot_data() {

        // call WP database
        global $wpdb;

        // do WP version check and get data accordingly
        if ( get_bloginfo( 'version' ) < '3.4' ) {
            $theme_data = wp_get_theme(get_stylesheet_directory() . '/style.css');
            $theme = $theme_data['Name'] . ' ' . $theme_data['Version'];
        } else {
            $theme_data = wp_get_theme();
            $theme = $theme_data->Name . ' ' . $theme_data->Version;
        }

        // data checks for later
        $frontpage	= get_option('page_on_front');
        $frontpost	= get_option('page_for_posts');
        $mu_plugins = get_mu_plugins();
        $plugins	= get_plugins();
        $active		= get_option( 'active_plugins', array() );

        // multisite details
        $nt_plugins	= is_multisite() ? wp_get_active_network_plugins() : array();
        $nt_active	= is_multisite() ? get_site_option( 'active_sitewide_plugins', array() ) : array();
        $ms_sites	= is_multisite() ? get_sites() : null;

        // yes / no specifics
        $ismulti	= is_multisite() ? __( 'Yes', 'system-snapshot-report' ) : __( 'No', 'system-snapshot-report' );
        $safemode	= ini_get( 'safe_mode' ) ? __( 'Yes', 'system-snapshot-report' ) : __( 'No', 'system-snapshot-report' );
        $wpdebug	= defined( 'WP_DEBUG' ) ? WP_DEBUG ? __( 'Enabled', 'system-snapshot-report' ) : __( 'Disabled', 'system-snapshot-report' ) : __( 'Not Set', 'system-snapshot-report' );
        $tbprefx	= strlen( $wpdb->prefix ) < 16 ? __( 'Acceptable', 'system-snapshot-report' ) : __( 'Too Long', 'system-snapshot-report' );
        $fr_page	= $frontpage ? get_the_title( $frontpage ).' (ID# '.$frontpage.')'.'' : __( 'n/a', 'system-snapshot-report' );
        $fr_post	= $frontpage ? get_the_title( $frontpost ).' (ID# '.$frontpost.')'.'' : __( 'n/a', 'system-snapshot-report' );
        $errdisp	= ini_get( 'display_errors' ) != false ? __( 'On', 'system-snapshot-report' ) : __( 'Off', 'system-snapshot-report' );

        $jquchk		= wp_script_is( 'jquery', 'registered' ) ? $GLOBALS['wp_scripts']->registered['jquery']->ver : __( 'n/a', 'system-snapshot-report' );

        $sessenb	= isset( $_SESSION ) ? __( 'Enabled', 'system-snapshot-report' ) : __( 'Disabled', 'system-snapshot-report' );
        $usecck		= ini_get( 'session.use_cookies' ) ? __( 'On', 'system-snapshot-report' ) : __( 'Off', 'system-snapshot-report' );
        $useocck	= ini_get( 'session.use_only_cookies' ) ? __( 'On', 'system-snapshot-report' ) : __( 'Off', 'system-snapshot-report' );
        $hasfsock	= function_exists( 'fsockopen' ) ? __( 'Your server supports fsockopen.', 'system-snapshot-report' ) : __( 'Your server does not support fsockopen.', 'system-snapshot-report' );
        $hascurl	= function_exists( 'curl_init' ) ? __( 'Your server supports cURL.', 'system-snapshot-report' ) : __( 'Your server does not support cURL.', 'system-snapshot-report' );
        $hassoap	= class_exists( 'SoapClient' ) ? __( 'Your server has the SOAP Client enabled.', 'system-snapshot-report' ) : __( 'Your server does not have the SOAP Client enabled.', 'system-snapshot-report' );
        $hassuho	= extension_loaded( 'suhosin' ) ? __( 'Your server has SUHOSIN installed.', 'system-snapshot-report' ) : __( 'Your server does not have SUHOSIN installed.', 'system-snapshot-report' );
        $openssl	= extension_loaded('openssl') ? __( 'Your server has OpenSSL installed.', 'system-snapshot-report' ) : __( 'Your server does not have OpenSSL installed.', 'system-snapshot-report' );

        // start generating report
        $report	= '';
        $report	.= '### Begin System Info ###'."\n\n";
        // add filter for adding to report opening
        $report	.= apply_filters( 'snapshot_report_before', '' );

        $report	.= "\n".'** WORDPRESS DATA **'."\n";
        $report	.= 'Multisite:'.' '.$ismulti."\n";
        $report	.= 'SITE_URL:'.' '.site_url()."\n";
        $report	.= 'HOME_URL:'.' '.home_url()."\n";
        $report	.= 'WP Version:'.' '.get_bloginfo( 'version' )."\n";
        $report	.= 'Permalink:'.' '.get_option( 'permalink_structure' )."\n";
        $report	.= 'Cur Theme:'.' '.$theme."\n";
        $report	.= 'Post Types:'.' '.implode( ', ', get_post_types( '', 'names' ) )."\n";
        $report	.= 'Post Stati:'.' '.implode( ', ', get_post_stati() )."\n";
        // $report	.= 'User Count:'.' '.count( get_users() )."\n";

        $report	.= "\n\n".'** WORDPRESS CONFIG **'."\n";
        $report	.= 'WP_DEBUG:'.' '.$wpdebug."\n";
        $report	.= 'WP Memory Limit:'.' '.$this->num_convt( WP_MEMORY_LIMIT )/( 1024 ).'MB'."\n";
        $report	.= 'Table Prefix:'.' '.$wpdb->base_prefix."\n";
        $report	.= 'Prefix Length:'.' '.$tbprefx.' ('.strlen( $wpdb->prefix ).' characters)'."\n";
        $report	.= 'Show On Front:'.' '.get_option( 'show_on_front' )."\n";
        $report	.= 'Page On Front:'.' '.$fr_page."\n";
        $report	.= 'Page For Posts:'.' '.$fr_post."\n";

        if ( is_multisite() ) {
            $report	.= "\n\n".'** MULTISITE INFORMATION **'."\n";
            $report	.= 'Total Sites:'.' '.get_blog_count()."\n";
            $report	.= 'Base Site:'.' '.$ms_sites[0]['domain']."\n";
            $report	.= 'All Sites:'."\n";
            foreach ( $ms_sites as $site ) {
                if ($site['path'] != '/') {
                    $report .= ' ' . '- ' . $site['domain'] . $site['path'] . "\n";
                }
            }
            $report	.= "\n";
        }

        $report	.= "\n\n".'** SERVER DATA **'."\n";
        $report	.= 'jQuery Version'.' '.$jquchk."\n";
        $report	.= 'PHP Version:'.' '.PHP_VERSION."\n";
        $report	.= 'MySQL Version:'.' '.$wpdb->db_version()."\n";
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $report	.= 'Server Software:'.' '.$_SERVER['SERVER_SOFTWARE']."\n";

        $report	.= "\n".'** PHP CONFIGURATION **'."\n";
        $report	.= 'Safe Mode:'.' '.$safemode."\n";
        $report	.= 'Memory Limit:'.' '.ini_get( 'memory_limit' )."\n";
        $report	.= 'Upload Max:'.' '.ini_get( 'upload_max_filesize' )."\n";
        $report	.= 'Post Max:'.' '.ini_get( 'post_max_size' )."\n";
        $report	.= 'Time Limit:'.' '.ini_get( 'max_execution_time' )."\n";
        $report	.= 'Max Input Vars:'.' '.ini_get( 'max_input_vars' )."\n";
        $report	.= 'Display Errors:'.' '.$errdisp."\n";
        $report	.= 'Sessions:'.' '.$sessenb."\n";
        $report	.= 'Session Name:'.' '.esc_html( ini_get( 'session.name' ) )."\n";
        $report	.= 'Cookie Path:'.' '.esc_html( ini_get( 'session.cookie_path' ) )."\n";
        $report	.= 'Save Path:'.' '.esc_html( ini_get( 'session.save_path' ) )."\n";
        $report	.= 'Use Cookies:'.' '.$usecck."\n";
        $report	.= 'Use Only Cookies:'.' '.$useocck."\n";
        $report	.= 'FSOCKOPEN:'.' '.$hasfsock."\n";
        $report	.= 'cURL:'.' '.$hascurl."\n";
        $report	.= 'SOAP Client:'.' '.$hassoap."\n";
        $report	.= 'SUHOSIN:'.' '.$hassuho."\n";
        $report	.= 'OpenSSL:'.' '.$openssl."\n";

        $report	.= "\n\n".'** PLUGIN INFORMATION **'."\n";
        if ( $plugins && $mu_plugins ) {
            $report	.= 'Total Plugins:'.' '.( count( $plugins ) + count( $mu_plugins ) + count( $nt_plugins ) )."\n";
        }

        // output must-use plugins
        if ( $mu_plugins ) {
            $report .= 'Must-Use Plugins: (' . count($mu_plugins) . ')' . "\n";
            foreach ($mu_plugins as $mu_path => $mu_plugin) {
                $report .= ' ' . '- ' . $mu_plugin['Name'] . ' ' . $mu_plugin['Version'] . "\n";
            }
            $report .= "\n";
        }

        // if multisite, grab active network as well
        if ( is_multisite() ) {
            // active network
            $report .= 'Network Active Plugins: (' . count($nt_plugins) . ')' . "\n";

            foreach ($nt_plugins as $plugin_path) {
                if (array_key_exists($plugin_base, $nt_plugins)) {
                    continue;
                }

                $plugin = get_plugin_data($plugin_path);

                $report .= ' ' . '- ' . $plugin['Name'] . ' ' . $plugin['Version'] . "\n";
            }
            $report .= "\n";

        }

        // output active plugins
        if ( $plugins ) {
            $report .= 'Active Plugins: (' . count($active) . ')' . "\n";
            foreach ($plugins as $plugin_path => $plugin) {
                if (!in_array($plugin_path, $active))
                    continue;
                $report .= ' ' . '- ' . $plugin['Name'] . ' ' . $plugin['Version'] . "\n";
            }
            $report .= "\n";
        }

        // output inactive plugins
        if ( $plugins ) {
            $report .= 'Inactive Plugins: (' . (count($plugins) - count($active)) . ')' . "\n";
            foreach ($plugins as $plugin_path => $plugin) {
                if (in_array($plugin_path, $active)) {
                    continue;
                }
                $report .= ' ' . '- ' . $plugin['Name'] . ' ' . $plugin['Version'] . "\n";
            }
            $report	.= "\n";
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $tables = $wpdb->get_results("SHOW TABLES");
        $report .= "MySQL Tables\n";
        foreach ($tables as $table) {
            foreach ($table as $t) {
                $report .= ' ' . '- ' . $t . "\n";
            }
        }
        $report	.= "\n";

        // add filter for end of report
        $report	.= apply_filters( 'snapshot_report_after', '' );

        // end it all
        $report	.= "\n".'### End System Info ###';

        return $report;
    }
}
