<?php
/**
 * Plugin Name: WP VisitorFlow
 * Plugin URI: https://www.datacodedesign.de/index.php/wp-visitorflow/
 * Description: Detailed web analytics and visualization of your website's visitor flow
 * Version: 1.5.8
 * Author: Onno Gabriel, DataCodeDesign
 * Author URI: http://www.onno-gabriel.de
 * License: GPL2
 * Text Domain: wp-visitorflow
 */

/**
 * Copyright 2019 Onno Gabriel
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

 // Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

// Global constants
define( 'WP_VISITORFLOW_VERSION', '1.5.8' );
define( 'WP_VISITORFLOW_REQUIRED_PHP_VERSION', '5.4.0' );
define( 'WP_VISITORFLOW_PLUGIN_PATH', trailingslashit( dirname(  __FILE__ ) ) );
define( 'WP_VISITORFLOW_PLUGIN_URL', trailingslashit( plugins_url(  'wp-visitorflow' ) ) );

// Set default timezone
if ( get_option('timezone_string') ) {
	date_default_timezone_set( get_option('timezone_string') );
}

// Check required PHP version
if ( ! version_compare( phpversion(), WP_VISITORFLOW_REQUIRED_PHP_VERSION, ">=" ) ) {
	// Warning message in admin area
	if ( is_admin() ) {
		function wp_visitorflow_php_notice() {
			$class = 'notice notice-error';
			$message = __(
				sprintf(
					'The WP VisitorFlow plugin requires at least PHP version %s, but installed is version %s.',
					WP_VISITORFLOW_REQUIRED_PHP_VERSION,
					PHP_VERSION
				),
				'wp-visitorflow'
			);
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
		add_action('admin_notices', 'wp_visitorflow_php_notice');
	}
	return;
}

/**
 * SPL Class Autoloader
 *
 * @param string $class name of an class-file (without file extension)
 */
function wp_visitorflow_autoload( $class ) {

	$classes = array(
		'WP_VisitorFlow_Admin',
		'WP_VisitorFlow_Admin_Export',
		'WP_VisitorFlow_Admin_Overview',
		'WP_VisitorFlow_Admin_Page',
		'WP_VisitorFlow_Admin_Page_Metabox',
		'WP_VisitorFlow_Admin_Page_Timeframe',
		'WP_VisitorFlow_Admin_Plots',
		'WP_VisitorFlow_Admin_Settings',
		'WP_VisitorFlow_Admin_Single',
		'WP_VisitorFlow_Admin_Tables',
		'WP_VisitorFlow_Admin_Website',
		'WP_VisitorFlow_Analysis',
		'WP_VisitorFlow_Config',
		'WP_VisitorFlow_Database',
		'WP_VisitorFlow_Maintenance',
		'WP_VisitorFlow_Recorder',
		'WP_VisitorFlow_Setup',
		'WP_VisitorFlow_Table',
		'WP_List_Table_WPVF'
	);

	if ( in_array( $class, $classes, true ) ) {
		require_once(
			sprintf(
				'%sincludes/classes/class-%s.php',
				WP_VISITORFLOW_PLUGIN_PATH,
				strtolower( str_replace( '_', '-', $class ) )
			)
		);
	}
}
spl_autoload_register( 'wp_visitorflow_autoload' );


// Check fresh installation or version update
$wpvf_version = get_option('wp_visitorflow_plugin_version');
if ( $wpvf_version != WP_VISITORFLOW_VERSION ) {
	// Create or update tables
	WP_VisitorFlow_Setup::init();
	WP_VisitorFlow_Setup::createTables();
	// Call post_update function is case of performed plugin update
	WP_VisitorFlow_Setup::postUpdate( $wpvf_version, get_option('wp_visitorflow_plugin_version') );
}


// On admin panel
if ( is_admin() ) {

	// Translations
	function wp_visitorflow_internationalization()
	{
		$res = load_plugin_textdomain(
			'wp-visitorflow',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}
	add_action( 'init', 'wp_visitorflow_internationalization' );


	// Check if update finished correctly
	if ( get_option('wp_visitorflow_plugin_version') != WP_VISITORFLOW_VERSION ) {
		// Warning message inside the dashboard
		function wp_visitorflow_error_notice() {
			$class = 'notice notice-error';
			$message = __('An error occurred during installation/update of the WP VisitorFlow plugin.', 'wp-visitorflow');
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
		add_action('admin_notices', 'wp_visitorflow_error_notice');
		return;
	}

	WP_VisitorFlow_Admin::init();

	return;
}

$wpvfConfig = WP_VisitorFlow_Config::getInstance();
if ( ! $wpvfConfig->getSetting('use_frontend_js') ) {
	// Add the action hook at the end of the page delivery
	add_action('shutdown', 'wp_visitorflow_record_action');

}
else {
	// Load frontend in footer
	add_action( 'wp_footer', 'wp_visitorflow_frontend_action' );
}

function wp_visitorflow_record_action() {
	$wpvfConfig = WP_VisitorFlow_Config::getInstance();
	if ( $wpvfConfig->getSetting('record_visitorflow') == true ) {
		WP_VisitorFlow_Recorder::init();
		WP_VisitorFlow_Recorder::recordVisit();
	}
}

function wp_visitorflow_frontend_action() {
	include_once( WP_VISITORFLOW_PLUGIN_PATH . '/includes/functions/wp-visitorflow-frontend.php' );
	wp_visitorflow_record_action();
}

// Trigger data aggregation
if ( $wpvfConfig->getSetting('last_aggregation_date') != date("Y-m-d") ) {
	WP_VisitorFlow_Maintenance::init();
	WP_VisitorFlow_Maintenance::aggregateData();
}
// Trigger data clean up
if ( $wpvfConfig->getSetting('last_dbclean_date') != date("Y-m-d") ) {
	WP_VisitorFlow_Maintenance::init();
	WP_VisitorFlow_Maintenance::cleanupData();
}


// Register uninstall hook
register_uninstall_hook(
	__FILE__,
	array(
		'WP_VisitorFlow_Setup',
		'uninstall',
	)
);

// REST API
add_action( 'rest_api_init', 'wp_visitorflow_register_rest_routes' );
function wp_visitorflow_register_rest_routes() {
	$wpvfConfig = WP_VisitorFlow_Config::getInstance();
	if ( $wpvfConfig->getSetting('enable_app_access') == true
	  && version_compare( get_bloginfo('version'), '4.7', '>=' ) ) {
		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/functions/wp-visitorflow-rest-api.php';
	}

}

