<?php
/**
 *	Setup plugin database tables
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Setup")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Setup
{

	private static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
			WP_VisitorFlow_Database::init();
		}
	}


	public static function createTables() {
		$wp_prefix = WP_VisitorFlow_Database::getDB()->prefix;

		// Create tables (if not exist)
		$visits_table_name = $wp_prefix . 'visitorflow_visits';
		$sql_create_visits_table = ("CREATE TABLE $visits_table_name (
										id int(11) NOT NULL AUTO_INCREMENT,
										last_visit datetime NOT NULL,
										agent_name varchar(255) DEFAULT NULL,
										agent_version varchar(63) DEFAULT NULL,
										agent_engine varchar(255) DEFAULT NULL,
										os_name varchar(255) DEFAULT NULL,
										os_version varchar(63) DEFAULT NULL,
										os_platform varchar(255) DEFAULT NULL,
										ip varchar(255) DEFAULT NULL,
										PRIMARY KEY  (id),
										KEY agent_name (agent_name),
										KEY agent_version (agent_version),
										KEY agent_engine (agent_engine),
										KEY os_name (os_name),
										KEY os_version (os_version),
										KEY os_platform (os_platform)
									) CHARSET=utf8;");

		$pages_table_name = $wp_prefix . 'visitorflow_pages';
		$sql_create_pages_table  = ("CREATE TABLE $pages_table_name (
										id int(11) NOT NULL AUTO_INCREMENT,
										internal BOOL DEFAULT 0 NOT NULL,
										f_post_id int(11) NOT NULL DEFAULT '0',
										title varchar(255) NOT NULL,
										PRIMARY KEY  (id)
									) CHARSET=utf8;");

		$flow_table_name = $wp_prefix . 'visitorflow_flow';
		$sql_create_flow_table   = ("CREATE TABLE $flow_table_name (
										id int(11) NOT NULL AUTO_INCREMENT,
										f_visit_id int(11),
										step int(11) NOT NULL,
										datetime datetime NOT NULL,
										f_page_id int(11) NOT NULL,
										PRIMARY KEY  (id),
										KEY f_visit_id (f_visit_id),
										KEY step (step),
										KEY datetime (datetime),
										KEY f_page_id (f_page_id)
									) CHARSET=utf8;");

		$meta_table_name = $wp_prefix . 'visitorflow_meta';
		$sql_create_meta_table  = ("CREATE TABLE $meta_table_name (
									id int(11) NOT NULL AUTO_INCREMENT,
									datetime datetime NOT NULL,
									type varchar(31) NOT NULL,
									label varchar(255) NOT NULL,
									value varchar(1023) NOT NULL,
									PRIMARY KEY  (id),
									KEY type (type),
									KEY label (label)
									) CHARSET=utf8;");

		$aggregation_table_name = $wp_prefix . 'visitorflow_aggregation';
		$sql_create_aggregation_table  = ("CREATE TABLE $aggregation_table_name (
											id int(11) NOT NULL AUTO_INCREMENT,
											type varchar(31) NOT NULL,
											date date NOT NULL,
											value int(11) NOT NULL,
											PRIMARY KEY  (id),
											KEY type (type),
											UNIQUE KEY typedate (type,date)
											) CHARSET=utf8;");

		// Include the dbDelta function from WP core:
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Create/Alter tables:
		dbDelta($sql_create_visits_table);
		dbDelta($sql_create_pages_table);
		dbDelta($sql_create_flow_table);
		dbDelta($sql_create_meta_table);
		dbDelta($sql_create_aggregation_table);

		// Set WP VisitorFlow version
		update_option('wp_visitorflow_plugin_version', WP_VISITORFLOW_VERSION);

	}

	/**
	 * Post Update, called after installation or update
	 *
	 * @var string $old_version
	 * @var string $new_version
	 **/
	public static function postUpdate($old_version = false, $new_version = false) {

		if ($old_version && $new_version) {
			WP_VisitorFlow_Database::storeMeta('log', 'newversion', 'Update from version ' . $old_version . ' to version ' . $new_version);
		}

		$message = '';
		$db = WP_VisitorFlow_Database::getDB();
		$pages_table = WP_VisitorFlow_Database::getTableName('pages');

		// Initialize "Pages" table with three standard pages ('unknown', 'self' and startpage of WP site)
		$result = $db->get_row("SELECT id FROM $pages_table WHERE f_post_id='0' AND title='unknown';");
		if (! isset($result->id)) {
			$res = $db->insert(
				$pages_table,
				array(
					'id'   		=> 1,
					'internal'  => 0,
					'f_post_id'	=> 0,
					'title'		=> 'unknown'
				),
				array('%d', '%d', '%d', '%s')
			);
			$message .= __('Initial page "unknown" added to pages table', 'wp-visitorflow') . '<br>';
		}

		$result = $db->get_row("SELECT id FROM $pages_table WHERE f_post_id='0' AND title='self';");
		if (! isset($result->id)) {
			$res = $db->insert(
				$pages_table,
				array(
					'id'   		=> 2,
					'internal'   => 1,
					'f_post_id'  => 0,
					'title'		=> 'self'
				),
				array('%d', '%d', '%d', '%s')
			);
			$message .= __('Initial page "self" added to pages table', 'wp-visitorflow') . '<br>';
		}

		$frontpage_id = get_option('page_on_front'); // Front page ID (set by WordPress)
		$result = $db->get_row("SELECT id FROM $pages_table WHERE f_post_id='" . $frontpage_id . "';");
		if (! isset($result->id)) {
			$res = $db->replace(
				$pages_table,
				array(
					'id'   		=> 3,
					'internal'   => 1,
					'f_post_id'  => $frontpage_id,
					'title'		=> get_the_title( $frontpage_id )
				),
				array('%d', '%d', '%d', '%s')
			);
			$message .= __('Front page added to pages table', 'wp-visitorflow') . '<br>';
		}

		if ($message) {
			WP_VisitorFlow_Database::storeMeta('log', 'initpages', $message);
		}

		$message .= self::setStartDatetimes();

		# Check cache folder and create it, if not existing)
		$export_dir = WP_CONTENT_DIR . '/extensions/';
		if (! file_exists( $export_dir ) ) {
			mkdir( $export_dir );
		}
		$export_dir .= 'wp-visitorflow/';
		if (! file_exists( $export_dir ) ) {
			mkdir( $export_dir );
		}
		$export_dir .= 'cache/';
		if (! file_exists( $export_dir ) ) {
			mkdir( $export_dir );
		}

		return $message;
	}

	/**
	 * Uninstall data tables and options
	 **/
	public static function uninstall() {

		// Delete all plugin's options
		delete_option( 'wp_visitorflow' );
		delete_option( 'wp_visitorflow_plugin_version' );
		// For site options in multisite
		delete_site_option( 'wp-visitorflow' );
		delete_site_option( 'wp_visitorflow_plugin_version' );

		//drop tables
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_visits" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_pages" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_flow" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_meta" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}visitorflow_aggregation" );
	}


	/**
	 * Find and set start datetimes for overall databe and for flow data table
	 **/
	public static function setStartDatetimes() {
		$message = '';

		$db = WP_VisitorFlow_Database::getDB();
		$flow_table = WP_VisitorFlow_Database::getTableName('flow');
		$aggregation_table = WP_VisitorFlow_Database::getTableName('aggregation');

		// Set flow data start date/time (= mininum datetime in flow table)
		$new_startdatetime = $db->get_var( "SELECT MIN(datetime) FROM $flow_table;" );
		if (! $new_startdatetime) {
			$new_startdatetime = self::$config->getDatetime();
		}
		if ($new_startdatetime != self::$config->getSetting('flow-startdatetime')) {
			self::$config->setSetting('flow-startdatetime', $new_startdatetime, 1);
			$message .= sprintf( __('New flow data start date = %s.', 'wp-visitorflow'),
								 date_i18n( get_option( 'date_format' ), strtotime($new_startdatetime) ) ) . '<br>';
		}

		// Set overall db data start date/time (= mininum date in aggregation table)
		$new_db_startdatetime = $db->get_var( "SELECT MIN(date) FROM $aggregation_table;" );
		if (! $new_db_startdatetime) {
			$new_db_startdatetime = self::$config->getSetting('flow-startdatetime');
		}
		if ($new_db_startdatetime != self::$config->getSetting('db-startdatetime')) {
			self::$config->setSetting('db-startdatetime', $new_db_startdatetime, 1);
			$message .= sprintf( __('New db data start date = %s.', 'wp-visitorflow'),
								 date_i18n( get_option( 'date_format' ), strtotime($new_db_startdatetime) ) ) . '<br>';
		}

		// counters-startdatetime set?
		if (! self::$config->getSetting('counters-startdatetime')) {
			self::$config->setSetting('counters-startdatetime', $new_db_startdatetime, 1);
		}

		if ($message) {
			WP_VisitorFlow_Database::storeMeta('log', 'setstart', $message);
		}

		return $message;
	}



}

endif;	// Prevent multiple class definitions
