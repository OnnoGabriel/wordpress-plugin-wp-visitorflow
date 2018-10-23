<?php
/**
 *	Analysis class for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Analysis")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Analysis
{

	private static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
		}
	}

	/**
	 * Update DB info
	 *
	 * Updates the 'db-info' setting, which includes information on the database tables, which is used on several
	 * places in the data analysis.
	 **/
	public static function updateDBInfo( $force = false ) {

		// Return, if last update was less than 60 seconds ago (to reduce database access9
		if (! $force && self::$config->getSetting('db-info-timestamp') > time() - 60) {
			return;
		}

		// Short variables for database object and table names from visitorflow class
		$db = WP_VisitorFlow_Database::getDB();
		$visits_table = WP_VisitorFlow_Database::getTableName('visits');
		$flow_table = WP_VisitorFlow_Database::getTableName('flow');
		$pages_table = WP_VisitorFlow_Database::getTableName('pages');
		$meta_table = WP_VisitorFlow_Database::getTableName('meta');
		$aggregate_table  = WP_VisitorFlow_Database::getTableName('aggregation');

		$datetime_now = self::$config->getDatetime();
		$date_now = self::$config->getDatetime('Y-m-d');;

		// Calculate the minutes since flow startdatetime
		$datetime_diff = date_diff( date_create( self::$config->getSetting('flow-startdatetime') ),
									date_create( $datetime_now )
								  );
		$minutes_run = ( $datetime_diff->format('%a')*24*60 + $datetime_diff->format('%h')*60 + $datetime_diff->format('%i') );
		$perdayfactor = $minutes_run > 0 ? (24*60 / $minutes_run) : 1;

		// Calculate the minutes since db startdatetime
		$datetime_diff = date_diff( date_create( self::$config->getSetting('db-startdatetime') ),
									date_create( $datetime_now )
								  );
		$db_minutes_run = ( $datetime_diff->format('%a')*24*60 + $datetime_diff->format('%h')*60 + $datetime_diff->format('%i') );
		$db_perdayfactor = $db_minutes_run > 0 ? (24*60 / $db_minutes_run) : 1;

		// Calculate the minutes since counters startdatetime
		$datetime_diff = date_diff( date_create( self::$config->getSetting('counters-startdatetime') ),
									date_create( $datetime_now )
								  );
		$counters_minutes_run = ( $datetime_diff->format('%a')*24*60 + $datetime_diff->format('%h')*60 + $datetime_diff->format('%i') );
		$counters_perdayfactor = $counters_minutes_run > 0 ? (24*60 / $counters_minutes_run) : 1;

		// Get counts from DB tables
		$visits_count     = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										   WHERE type='visits';");
		$visits_14d_count = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 14 day);");
		$visits_7d_count  = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 7 day);");
		$visits_24h_count = $db->get_var("SELECT COUNT(*) FROM $flow_table
										   WHERE step='2'
										   AND date_add(datetime, interval 24 hour)>'$datetime_now';");
		$visits_14d_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 28 day)
										   AND date<subdate('$date_now', interval 14 day);");
		$visits_7d_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										   WHERE type='visits'
										   AND date>=subdate('$date_now', interval 14 day)
										   AND date<subdate('$date_now', interval 7 day);");
		$visits_24h_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										   WHERE type='visits'
										   AND date=subdate('$date_now', interval 7 day);");

		$pages = $db->get_row("SELECT COUNT(*) AS count_total,
											SUM(internal) AS count_internal
									 FROM   $pages_table
									 WHERE  id>='3';" );


		$hits_count = $db->get_var("SELECT SUM(value) FROM $aggregate_table
								    WHERE type='views-all';" );
		$hits_14d_count = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										WHERE type='views-all'
										AND date>=subdate('$date_now', interval 14 day);");
		$hits_7d_count  = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										WHERE type='views-all'
										AND date>=subdate('$date_now', interval 7 day);");
		$hits_24h_count = $db->get_var("SELECT COUNT(*) FROM $flow_table
										WHERE step>'1'
										AND date_add(datetime, interval 24 hour)>'$datetime_now';");
		$hits_14d_before = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										 WHERE type='views-all'
										 AND date>=subdate('$date_now', interval 28 day)
										 AND date<subdate('$date_now', interval 14 day);");
		$hits_7d_before  = $db->get_var("SELECT SUM(value) FROM $aggregate_table
										 WHERE type='views-all'
										 AND date>=subdate('$date_now', interval 14 day)
										 AND date<subdate('$date_now', interval 7 day);");
		$hits_24h_before = $db->get_var( "SELECT COUNT(*) FROM $flow_table
												 WHERE step>'1'
												 AND date_add(datetime, interval 192 hour)>'$datetime_now'
												 AND date_add(datetime, interval 168 hour)<='$datetime_now';");

		$meta_useragents_count = $db->get_var( "SELECT COUNT(*) FROM $meta_table WHERE type='useragent';" );

		$bots_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count bot';" );
		$uastring_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count uastring';" );
		$pagestring_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count pagestring';" );
		$exclusions_count = $db->get_var( "SELECT SUM(value) FROM $meta_table WHERE type='count exclusion';" );

		// Set DB info
		$db_info = array('last_update_timestamp' => self::$config->getSetting('db-info-timestamp'),
						 'minutes_run' => $minutes_run,
						 'db_minutes_run' => $db_minutes_run,
						 'perdayfactor' => $perdayfactor,
						 'db_perdayfactor' => $db_perdayfactor,
						 'visits_24h_count' => $visits_24h_count,
						 'visits_7d_count' => $visits_7d_count,
						 'visits_14d_count' => $visits_14d_count,
						 'visits_24h_before' => $visits_24h_before,
						 'visits_7d_before' => $visits_7d_before,
						 'visits_14d_before' => $visits_14d_before,
						 'visits_count' => $visits_count,
						 'pages_count' => $pages->count_total,
						 'pages_internal_count' => $pages->count_internal,
						 'hits_24h_count' => $hits_24h_count,
						 'hits_7d_count' => $hits_7d_count,
						 'hits_14d_count' => $hits_14d_count,
						 'hits_24h_before' => $hits_24h_before,
						 'hits_7d_before' => $hits_7d_before,
						 'hits_14d_before' => $hits_14d_before,
						 'hits_count' => $hits_count,
						 'meta_useragents_count' => $meta_useragents_count,
						 'bots_count' => $bots_count,
						 'counters_perdayfactor' => $counters_perdayfactor,
						 'exclusions_count' => $bots_count +  $uastring_count + $pagestring_count + $exclusions_count,
						);
		self::$config->setSetting('db_info', $db_info, 1);
		self::$config->setSetting('db-info-timestamp', time() );
	}


}

endif;	// Prevent multiple class definitions
