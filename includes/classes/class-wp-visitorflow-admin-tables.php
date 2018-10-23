<?php
/**
 *	This class manages all tables for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;


if (! class_exists("WP_VisitorFlow_Admin_Tables")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Tables
{

	public static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {
		if ( is_admin() && ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
		}
	}


	/**
	 * WP VisitorFlow Short Summary (Visits, Page Views etc.)
	 **/
	public static function summary() {
		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/tables/table-summary.php';

	}


	/**
	 * WP VisitorFlow Latest SE Key Words Table
	 **/
	public static function latestKeywordsTable() {
		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/tables/table-keywords.php';
	}


	/**
	 * WP VisitorFlow Bot Counts Table
	 **/
	public static function botCountsTable() {
		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/tables/table-bots.php';
	}


	/**
	 * WP VisitorFlow Exclusion Counts Table
	 **/
	public static function exclusionCountsTable() {
		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/tables/table-exclusions.php';
	}


	/**
	 * WP VisitorFlow DB Info Table
	 **/
	public static function dbInfoTable() {
		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/tables/table-dbinfo.php';
	}


	/**
	 * WP VisitorFlow User-Agent Strings Table
	 **/
	public static function latestUAStringsTable() {
		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/tables/table-uastrings.php';
	}
}

endif;	// Prevent multiple class definitions
