<?php
/**
 *	Website Flow Analysis
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Website")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Website
{

	private static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
		}

		// Enqueue sankey-steps.css
		wp_enqueue_style('sankey-steps-css', WP_VISITORFLOW_PLUGIN_URL . 'assets/css/sankey-steps.css');

		// Register and enqueue DoubleScrollbar JS
		wp_register_script('DoubleScroll_js', WP_VISITORFLOW_PLUGIN_URL . 'assets/js/jquery.doubleScroll.js' );
		wp_enqueue_script( 'DoubleScroll_js' );
	}

	/**
	 * Main
	 **/
	public static function main() {
		if ( ! self::$config ) {
			self::init();
		}

		if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$db = WP_VisitorFlow_Database::getDB();
		$flow_table = WP_VisitorFlow_Database::getTableName('flow');
		$pages_table = WP_VisitorFlow_Database::getTableName('pages');
		$visits_table = WP_VisitorFlow_Database::getTableName('visits');
		$aggregation_table = WP_VisitorFlow_Database::getTableName('aggregation');
		$meta_table = WP_VisitorFlow_Database::getTableName('meta');

		// Load user settings
		self::$config->loadUserSettings();

		// Minimum reader capability (can be set in "general settings" section):
		$reader_cap = self::$config->getSetting('read_access_capability');
		$hit_count = 25;

		// Include TimeframePage class and instantiate
		$timeframePage = new WP_VisitorFlow_Admin_Page_Timeframe($reader_cap);

		// Start and end of the time interval selection (permanently stored in options)
		$datetimestart = $timeframePage->getTimeframeStart();
		$datetimestop = $timeframePage->getTimeframeStop();
		if ( ! $timeframePage->getTimeframeStart() || ! $timeframePage->getTimeframeStop() ) {
			$datetimestart = self::$config->getUserSetting('datetimestart');
			$datetimestop  = self::$config->getUserSetting('datetimestop');
			$timeframePage->setTimeframe($datetimestart, $datetimestop);
		}

		// Set Tabs
		$timeframePage->setTabs( array( 'flow' 		=> array( 'title' => __('Visitor Flow', 'wp-visitorflow'),		'min_role' => $reader_cap),
										'visitors'  => array( 'title' => __('Visitors', 'wp-visitorflow'),			'min_role' => $reader_cap),
										'referrers' => array( 'title' => __('Referrers', 'wp-visitorflow'),    	'min_role' => $reader_cap),
										'pages'   	=> array( 'title' => __('Visited Pages', 'wp-visitorflow'),   	'min_role' => $reader_cap),
										)
								);

		if ($timeframePage->get_current_tab() == 'visitors') {
			$timeframePage->printHeader(
				__('Website Visitors', 'wp-visitorflow'),
				__('Distribution of remote client\'s browsers and operation systems and a detailed list of recent visitors', 'wp-visitorflow')
			);
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/analysis/website-visitors.php';
		}
		elseif ($timeframePage->get_current_tab() == 'referrers') {
			$timeframePage->printHeader(
				__('Website Referrers', 'wp-visitorflow'),
				__('The top referrers to your website.', 'wp-visitorflow')
			);
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/analysis/website-referrers.php';
		}
		elseif ($timeframePage->get_current_tab() == 'pages') {
			$timeframePage->printHeader(
				__('Website Pages', 'wp-visitorflow'),
				__('The most visited pages on your website.', 'wp-visitorflow')
			);
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/analysis/website-pages.php';
		}
		else {
			$timeframePage->printHeader(
				__('Website Visitor Flow', 'wp-visitorflow'),
				__('The total visitor flow: step-by-step diagramm of the visitors interactions with your website', 'wp-visitorflow')
			);
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/analysis/flow-per-step.php';
		}

		if (self::$config->getUserSetting('datetimestart') !=  $timeframePage->getTimeframeStart() ) {
			self::$config->setUserSetting('datetimestart', $timeframePage->getTimeframeStart(), 0);
		}
		if (self::$config->getUserSetting('datetimestop')  !=  $timeframePage->getTimeframeStop() ) {
			self::$config->setUserSetting('datetimestop',  $timeframePage->getTimeframeStop(), 0);
		}

		self::$config->saveUserSettings();

		$timeframePage->printFooter();

	}

}

endif;	// Prevent multiple class definitions
