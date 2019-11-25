<?php
/**
 *	Maintenance class for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Maintenance")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Maintenance
{

	private static $config = 0;

	private static $db;						// WordPress database object
	private static $table_name = array();			// Hash array with db table names

	/**
	 * Init
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
			WP_VisitorFlow_Database::init();
		}
	}


	/**
	 * Aggregate old data
	 **/
	public static function aggregateData() {

		// Aggregation already running?
		if (self::$config->getSetting('data_aggregation_running')) {
			// Running aggregation process younger than one hour? => wait for it.
			if (time() - self::$config->getSetting('data_aggregation_running') < 60*60) {
				return false;
			}
		}
		self::$config->setSetting('data_aggregation_running', time(), true);

		// Get next aggregation date
		if (self::$config->getSetting('last_aggregation_date')) {
			$aggregation_date = new DateTime( self::$config->getSetting('last_aggregation_date') );
			$aggregation_date->modify('+1 day');
		}
		else {
			$flow_startdatetime = new DateTime( self::$config->getSetting('flow-startdatetime') );
			$aggregation_date = new Datetime( $flow_startdatetime->format('Y-m-d') );
		}

		// Last check: aggregation date younger than yesterday (=today or later)? If yes, return without any data aggregation
		$yesterday = new DateTime( self::$config->getDatetime('Y-m-d') );
		$yesterday->modify('-1 day');
		if ($aggregation_date > $yesterday) {
			return false;
		}

		// Do data aggregation for next aggregation date
		$result = self::aggregateDataPerDay( $aggregation_date->format('Y-m-d') );

		if ( $result ) {
			// Save settings
			self::$config->setSetting( 'data_aggregation_running', false );
			self::$config->setSetting( 'last_aggregation_date', $aggregation_date->format('Y-m-d') );
			self::$config->saveSettings();
			return true;
		}
		return false;
	}


	/**
	 * Perform data aggregation for day $date
	 *
	 * @var string $date
	 * @return boolean
	 **/
	public static function aggregateDataPerDay( $date = false ) {
		if ( ! $date ) { return false; }

		// Get data for date $date
		$data = WP_VisitorFlow_Database::getData( $date );

		// Insert into or update data in aggregation table
		$db = WP_VisitorFlow_Database::getDB();
		$aggregation_table = WP_VisitorFlow_Database::getTableName('aggregation');

		foreach ($data as $key => $value) {
			$db->replace(
				$aggregation_table,
				array(
					'type' => $key,
					'date' => $date,
					'value' => $value
				),
				array('%s', '%s', '%d')
			);

		}

		WP_VisitorFlow_Database::storeMeta( 'log', 'aggregat', 'Data aggregated for date ' . $date );

		return true;
	}


	/**
	 * Clean up old data
	 **/
	public static function cleanupData() {

		$db = WP_VisitorFlow_Database::getDB();

		$visits_table = WP_VisitorFlow_Database::getTableName('visits');
		$pages_table  = WP_VisitorFlow_Database::getTableName('pages');
		$flow_table   = WP_VisitorFlow_Database::getTableName('flow');
		$meta_table   = WP_VisitorFlow_Database::getTableName('meta');


		$start_clean_date = new DateTime( self::$config->getDatetime('Y-m-d') );
		$start_clean_date->modify('-' . self::$config->getSetting('flowdata_storage_time') . ' day');

		$clean_date_string = $start_clean_date->format('Y-m-d');
		$message = '';

		// Clear "Flow" table
		$result = $db->query(
			$db->prepare(
				"DELETE FROM $flow_table WHERE datetime<'%s';",
				$clean_date_string
			)
		);
		if ($result) {
			$message = sprintf(__('%s page hits older than %s cleaned.', 'wp-visitorflow'), $result, $clean_date_string) . '<br>';
		}

		// Clear "Pages" table
		$result = $db->query(
			"DELETE FROM $pages_table
				   WHERE NOT EXISTS (
							SELECT id
							FROM $flow_table
							WHERE $flow_table.f_page_id=$pages_table.id
						)
					 AND id>'3';"
		);
		if ($result) {
			$message .= sprintf(__('%s pages older than %s cleaned.', 'wp-visitorflow'), $result, $clean_date_string) . '<br>';
		}

		// Clear "Visits" table
		$result = $db->query(
			"DELETE FROM $visits_table
				   WHERE NOT EXISTS (
							SELECT id
							FROM $flow_table
							WHERE $flow_table.f_visit_id=$visits_table.id
						)"
		);
		if ($result) {
			$message .= sprintf(__('%s visits older than %s cleaned.', 'wp-visitorflow'), $result, $clean_date_string) . '<br>';
		}

		// Clear "Meta" table
		$result = $db->query(
			$db->prepare(
				"DELETE FROM $meta_table WHERE datetime<'%s';",
				$clean_date_string)
		);
		if ($result) {
			$message .= sprintf(__('%s meta entries older than %s cleaned.', 'wp-visitorflow'), $result, $clean_date_string) . '<br>';
		}

		if ($message) {
			WP_VisitorFlow_Database::storeMeta('log', 'cleanup', $message);
		}

		WP_VisitorFlow_Setup::init();
		$message .= WP_VisitorFlow_Setup::setStartDatetimes();

		// Set last db clean-up to current date
		self::$config->setSetting('last_dbclean_date', date("Y-m-d"), 1);

		// Optimize database tables
		// $db->query("OPTIMIZE TABLE $flow_table");
		// $db->query("OPTIMIZE TABLE $pages_table");
		// $db->query("OPTIMIZE TABLE $visits_table");
		// $db->query("OPTIMIZE TABLE $meta_table");

		return $message;
	}

}

endif;	// Prevent multiple class definitions
