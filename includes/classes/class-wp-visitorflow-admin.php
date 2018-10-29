<?php
/**
 *	Admin class for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin
{

	private static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {


		if ( is_admin() && ! self::$config ) {

			self::$config = WP_VisitorFlow_Config::getInstance();

			WP_VisitorFlow_Database::init();
			WP_VisitorFlow_Admin_Overview::init();
			WP_VisitorFlow_Admin_Tables::init();

			// Add admin menu hook
			add_action( 'admin_menu', array('WP_VisitorFlow_Admin', 'adminMenu' ) );

			// Add dashboard widget
			add_action( 'wp_dashboard_setup', array('WP_VisitorFlow_Admin', 'dashboardWidget' ) );

			// Add plugin settings link to plugins list
			add_filter( 'plugin_action_links', array('WP_VisitorFlow_Admin', 'addSettingsLink'), 10, 2);

			// Add hook to posts table
			add_filter( 'manage_posts_columns' , array('WP_VisitorFlow_Admin', 'postsAddWpVisitorColumn') );
			add_action( 'manage_posts_custom_column' , array('WP_VisitorFlow_Admin', 'postsWpVisitorColumn', 10, 2 ) );

			// Add hook to pages table
			add_filter( 'manage_pages_columns', array('WP_VisitorFlow_Admin', 'pagesAddWpVisitorColumn' ) );
			add_action( 'manage_pages_custom_column' , array('WP_VisitorFlow_Admin', 'pagesWpVisitorColumn', 10, 2 ) );

		}

	}


	/**
	 * Add primary admin menu
	 */
	public static function adminMenu() {
		if (! is_admin() ) { return false; }

		// Minimum reader capability (can be set in "general settings" section):
		$reader_cap = self::$config->getSetting('read_access_capability');
		// Minimum admin capability (can be set in "general settings" section):
		$admin_cap = self::$config->getSetting('admin_access_capability');

		// // Add the top level menu.
		add_menu_page(
			__('VisitorFlow', 'wp-visitorflow'),
			__('VisitorFlow', 'wp-visitorflow'),
			$reader_cap,
			'wpvf_menu',
			'__return_true', '
			dashicons-randomize'
		);

		$thisPage = new WP_VisitorFlow_Admin_Page_Metabox(
			'wpvf_menu',
			'WP VisitorFlow &ndash; ' . __('Overview','wp-visitorflow'),
			__('Detailed web analytics and visualization of your website\'s visitor flow.', 'wp-visitorflow'),
			__('Overview','wp-visitorflow'),
			$reader_cap,
			'wpvf_menu',
			array('WP_VisitorFlow_Admin_Overview', 'overviewMetaboxes'),
			array('WP_VisitorFlow_Admin_Overview', 'overviewHeader')
		);

		add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Full Website Analytics', 'wp-visitorflow'), 	__('Full Website', 'wp-visitorflow'), 	$reader_cap,  'wpvf_mode_website',		array('WP_VisitorFlow_Admin_Website', 'main') );
		add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Single Page Analytics', 'wp-visitorflow'), 	__('Single Page', 'wp-visitorflow'), 	$reader_cap,  'wpvf_mode_singlepage',	array('WP_VisitorFlow_Admin_Single', 'main') );
		add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Data Export', 'wp-visitorflow'), 				__('Data Export', 'wp-visitorflow'),   	$admin_cap,   'wpvf_admin_export',	    array('WP_VisitorFlow_Admin_Export', 'main') );
		add_submenu_page('wpvf_menu', 'WP VisitorFlow &ndash; ' . __('Settings', 'wp-visitorflow'), 				__('Settings', 'wp-visitorflow'), 	   	$admin_cap,   'wpvf_admin_settings',	array('WP_VisitorFlow_Admin_Settings', 'main')) ;

		// Enqueue css file
		wp_enqueue_style('wpvf', WP_VISITORFLOW_PLUGIN_URL  . 'assets/css/wp_visitorflow.min.css');
		wp_enqueue_style('wpvf-bootstrap-grid', WP_VISITORFLOW_PLUGIN_URL  . 'assets/css/bootstrap_grid12.min.css');
	}


	/**
	 * Add Dashboard Widget
	 **/
	public static function dashboardWidget()
	{
		if (! is_admin() ) { return false; }

		wp_add_dashboard_widget(
			'wpvf',
			'WP VisitorFlow',
			'WP_VisitorFlow_Admin_Tables::summary',
			'wp_visitorflow_overview'
		);
	}


	/**
	 * Add plugin's settings to plugins list
	 */
	public static function addSettingsLink($links, $file) {
		if ( $file == 'wp-visitorflow/wp-visitorflow.php' ) {
			/* Insert the link at the end*/
			$links['settings'] = sprintf( '<a href="%s"> %s </a>', admin_url( 'admin.php?page=wpvf_admin_settings' ), __( 'Settings', 'plugin_domain' ) );
		}
		return $links;
	}


	/**
	 * Add column "WP VisitorFlow" to the manage Posts Screen
	 */
	public static function postsAddWpVisitorColumn($columns) {
		return array_merge(
			$columns,
			array('wp-visitorflow' => __('WP VisitorFlow'))
		);
	}
	public static function postsWpVisitorColumn( $column, $post_id ) {
		if ($column == 'wp-visitorflow') {
			$url = admin_url( 'admin.php?page=wpvf_mode_singlepage&amp;select_post_id=' .  $post_id);
			echo '<a class="wpvf wpvfflow" href="' . $url . '">' . __('Flow', 'wp-visitorflow') . '</a>';
		}
	}

	/**
	 * Add column "WP VisitorFlow" to the manage Pages Screen
	 */
	public static function pagesAddWpVisitorColumn($columns) {
		return array_merge(
			$columns,
			array('wp-visitorflow' => __('WP VisitorFlow'))
		);
	}
	public static function pagesWpVisitorColumn( $column, $post_id ) {
		if ($column == 'wp-visitorflow') {
			$url = admin_url( 'admin.php?page=wpvf_mode_singlepage&amp;select_post_id=' .  $post_id);
			echo '<a class="wpvf wpvfflow" href="' . $url . '">' . __('Flow', 'wp-visitorflow') . '</a>';
		}
	}

	/**
	 * Get time difference in words
	 **/
	public static function getNiceTimeDifference( $datetime1, $datetime2 ) {
		$date1 = new DateTime($datetime1);
		$date2 = new DateTime($datetime2);

		$date_diff = $date2->diff($date1);
		$years =$date_diff->format('%y');
		$months = $date_diff->format('%m');
		if ($months > 3) { return ( $years * 12 +  $months ) . ' ' . __('months', 'wp-visitorflow'); }
		$days = $date_diff->format('%a');
		if ($days > 1) { return number_format_i18n($days) . ' ' . __('days', 'wp-visitorflow'); }
		$hours = $date_diff->format('%h') + $days * 24;
		if ($hours > 1) { return $hours . ' ' . __('hours', 'wp-visitorflow'); }
		$mins = $hours * 60 + $date_diff->format('%i');
		return $mins . ' ' . __('minutes', 'wp-visitorflow');
	}

}

endif;	// Prevent multiple class definitions
